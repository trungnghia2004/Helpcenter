<?php

namespace App\Http\Controllers;

use App\Notifications\NewOrderNotification;
use App\Notifications\OrderConfirmationForCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class OrdersController extends Controller
{
    public function index(Request $request)
    {
        $cartData = json_decode($request->input('cart_data'), true) ?: [];
        $discountCode = $request->input('discount_code');

        $cartDetails = [];
        foreach ($cartData as $item) {
            $row = DB::selectOne(
                "SELECT 
                    cd.id,
                    cd.productDetailID,
                    cd.quantity,
                    pd.prdID,
                    p.productName,
                    p.productSellPrice,
                    s.sizeName,
                    c.colorName,
                    img.imageLink AS firstImage
                 FROM cart_details cd
                 JOIN product_details pd ON cd.productDetailID = pd.id
                 JOIN products p ON pd.prdID = p.productID
                 LEFT JOIN sizes s ON pd.sizeId = s.sizeId
                 LEFT JOIN colors c ON pd.colorId = c.colorId
                 LEFT JOIN (
                    SELECT pi.prdID, pi.imageLink
                    FROM product_images pi
                    JOIN (
                        SELECT prdID, MIN(imageID) AS firstImageID
                        FROM product_images
                        GROUP BY prdID
                    ) x ON pi.imageID = x.firstImageID
                 ) img ON img.prdID = pd.prdID
                 WHERE cd.id = ?",
                [$item['id'] ?? null]
            );
            if ($row) {
                $detail = new \stdClass();
                $detail->id = $row->id;
                $detail->productDetailID = $row->productDetailID;
                $detail->quantity = $item['quantity'] ?? $row->quantity;

                $product = new \stdClass();
                $product->productName = $row->productName;
                $product->productSellPrice = $row->productSellPrice;
                $product->firstImage = $row->firstImage ? (object)['imageLink' => $row->firstImage] : null;

                $productDetail = new \stdClass();
                $productDetail->id = $row->productDetailID;
                $productDetail->prdID = $row->prdID;
                $productDetail->product = $product;
                $productDetail->size = $row->sizeName ? (object)['sizeName' => $row->sizeName] : null;
                $productDetail->color = $row->colorName ? (object)['colorName' => $row->colorName] : null;

                $detail->productDetail = $productDetail;
                $cartDetails[] = $detail;
            }
        }

        $subtotal = 0;
        foreach ($cartDetails as $detail) {
            $price = $detail->productDetail->product->productSellPrice ?? 0;
            $quantity = $detail->quantity;
            $subtotal += $price * $quantity;
        }

        $discountValue = 0;
        if (!empty($discountCode)) {
            $program = DB::selectOne("SELECT * FROM discount_programs WHERE id = ?", [$discountCode]);
            if ($program) {
                $discountValue = $this->calculateDiscount($program, $subtotal);
            }
        }

        $total = max(0, $subtotal - $discountValue);
        $payments = collect(DB::select("SELECT * FROM payments"));

        return view('UserPage.Checkout', compact(
            'cartDetails',
            'total',
            'subtotal',
            'discountValue',
            'discountCode',
            'payments'
        ));
    }


    public function storeFromCustomer(Request $request)
    {
        $discountCode = $request->discountCode;
        $discountProgram = null;
        if (!empty($discountCode)) {
            $discountProgram = DB::selectOne("SELECT * FROM discount_programs WHERE id = ?", [$discountCode]);
        }

        $now = now();
        DB::beginTransaction();
        try {
            DB::insert(
                "INSERT INTO orders (cusID, adminID, orderPhoneNumber, shipping_street, shipping_city, shipping_district, shipping_ward, payID, staID, totalPrice, discount_program_id, created_at, updated_at, isPayment)
                 VALUES (?, NULL, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, 0)",
                [
                    Auth::id(),
                    $request->phone,
                    $request->street_address,
                    $request->city,
                    $request->district,
                    $request->ward,
                    $request->payment,
                    $request->total ?? 0,
                    $discountProgram->id ?? null,
                    $now,
                    $now,
                ]
            );
            $orderId = DB::getPdo()->lastInsertId();

            // Gom productDetail trùng
            $groupedDetails = [];
            foreach ($request->productDetails as $productDetail) {
                $key = $productDetail['productDetailID'];
                if (!isset($groupedDetails[$key])) {
                    $groupedDetails[$key] = [
                        'productDetailID' => $productDetail['productDetailID'],
                        'quantity' => 0,
                        'unitPrice' => $productDetail['unitPrice'],
                    ];
                }
                $groupedDetails[$key]['quantity'] += $productDetail['quantity'];
                $groupedDetails[$key]['unitPrice'] = $productDetail['unitPrice'];
            }

            foreach ($groupedDetails as $detail) {
                $existing = DB::selectOne(
                    'SELECT orderQuantity FROM order_details WHERE orderID = ? AND productDetailID = ?',
                    [$orderId, $detail['productDetailID']]
                );

                if ($existing) {
                    DB::update(
                        'UPDATE order_details SET orderQuantity = orderQuantity + ?, unitPrice = ?, updated_at = ? WHERE orderID = ? AND productDetailID = ?',
                        [
                            $detail['quantity'],
                            $detail['unitPrice'],
                            $now,
                            $orderId,
                            $detail['productDetailID'],
                        ]
                    );
                } else {
                    DB::insert(
                        'INSERT INTO order_details (orderID, productDetailID, orderQuantity, unitPrice, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?)',
                        [
                            $orderId,
                            $detail['productDetailID'],
                            $detail['quantity'],
                            $detail['unitPrice'],
                            $now,
                            $now,
                        ]
                    );
                }

                // Cập nhật tồn kho
                DB::update(
                    "UPDATE product_details 
                     SET productQuantity = GREATEST(productQuantity - ?, 0), updated_at = ?
                     WHERE id = ?",
                    [$detail['quantity'], $now, $detail['productDetailID']]
                );
            }

            // Xóa giỏ hàng của user
            $cartRow = DB::selectOne('SELECT cartID FROM cart WHERE userID = ?', [Auth::id()]);
            if ($cartRow) {
                DB::delete('DELETE FROM cart_details WHERE cartID = ?', [$cartRow->cartID]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // Gửi thông báo: cần model Order cho notification type-hint
        $orderModel = \App\Models\Order::find($orderId);
        if ($orderModel) {
            $admin = DB::selectOne("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
            if ($admin && $admin->email) {
                Notification::route('mail', $admin->email)->notify(new NewOrderNotification($orderModel));
            }
            $customerEmail = DB::selectOne("SELECT email FROM users WHERE id = ?", [Auth::id()]);
            if ($customerEmail && $customerEmail->email) {
                Notification::route('mail', $customerEmail->email)->notify(new OrderConfirmationForCustomer($orderModel));
            }
        }

        return redirect()->route('customerPage')->with('success', 'Đơn hàng đã được tạo thành công!');
    }



    public function showOrders(Request $request)
    {
        $status = $request->input('statusID');
        $keyword = $request->input('keyword');
        $perPage = 5;
        $page = max((int) $request->input('page', 1), 1);
        $offset = ($page - 1) * $perPage;

        $where = 'o.cusID = ?';
        $bindings = [Auth::id()];
        if (!empty($status)) {
            $where .= ' AND o.staID = ?';
            $bindings[] = $status;
        }
        if (!empty($keyword)) {
            $where .= ' AND o.orderID LIKE ?';
            $bindings[] = '%' . $keyword . '%';
        }

        $totalRow = DB::selectOne("SELECT COUNT(*) AS aggregate FROM orders o WHERE $where", $bindings);
        $total = $totalRow ? (int) $totalRow->aggregate : 0;

        $orders = DB::select(
            "SELECT o.*, s.statusValue
             FROM orders o
             LEFT JOIN status s ON o.staID = s.statusID
             WHERE $where
             ORDER BY o.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($bindings, [$perPage, $offset])
        );

        // Lấy order_details + sản phẩm ảnh để hiển thị ảnh
        $orderIds = array_map(fn($o) => $o->orderID, $orders);
        $details = [];
        if ($orderIds) {
            $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
            $details = DB::select(
                "SELECT od.*, pd.prdID, p.productName,
                        (SELECT imageLink FROM product_images WHERE prdID = p.productID ORDER BY imageID ASC LIMIT 1) AS firstImage
                 FROM order_details od
                 JOIN product_details pd ON od.productDetailID = pd.id
                 JOIN products p ON pd.prdID = p.productID
                 WHERE od.orderID IN ($placeholders)",
                $orderIds
            );
        }
        $detailsByOrder = [];
        foreach ($details as $d) {
            $detailsByOrder[$d->orderID][] = $d;
        }

        $results = [];
        foreach ($orders as $order) {
            $productImages = [];
            $productCount = 0;
            $firstProductId = null;
            if (!empty($detailsByOrder[$order->orderID])) {
                foreach ($detailsByOrder[$order->orderID] as $detail) {
                    if ($detail->firstImage) {
                        $productImages[] = $detail->firstImage;
                    }
                    $productCount += $detail->orderQuantity;
                    if ($firstProductId === null && isset($detail->prdID)) {
                        $firstProductId = $detail->prdID;
                    }
                }
            }

            $results[] = [
                'orderID' => $order->orderID,
                'payStatus' => $order->isPayment,
                'orderCode' => '#ORD-' . Carbon::parse($order->created_at)->format('Y-m-d') . '-' . $order->orderID,
                'orderDate' => Carbon::parse($order->created_at)->format('d/m/Y \l\ú H:i'),
                'expectedDelivery' => Carbon::parse($order->created_at)->addDays(3)->format('d/m/Y') . ' - ' . Carbon::parse($order->created_at)->addDays(5)->format('d/m/Y'),
                'status' => $order->statusValue ?? 'Không rõ',
                'statusClass' => $this->getStatusClass($order->statusValue ?? ''),
                'totalPrice' => number_format($order->totalPrice, 0, ',', '.') . 'đ',
                'productCount' => $productCount,
                'productImages' => $productImages,
                'product' => $firstProductId ? (object)['productID' => $firstProductId] : null,
            ];
        }

        // Thống kê trạng thái
        $statusCountsRows = DB::select(
            "SELECT staID, COUNT(*) AS total FROM orders WHERE cusID = ? GROUP BY staID",
            [Auth::id()]
        );
        $statusCounts = [];
        foreach ($statusCountsRows as $row) {
            $statusCounts[$row->staID] = $row->total;
        }
        $totalOrders = $total;

        $ordersPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $orders,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('UserPage.order-list', [
            'results' => $results,
            'orders' => $ordersPaginator,
            'status' => $status,
            'statusCounts' => $statusCounts,
            'totalOrders' => $totalOrders,
            'keyword' => $keyword,
        ]);
    }


    private function getStatusClass($status)
    {
        return match ($status) {
            'Đang chờ duyệt'   => 'status-pending',
            'Đã duyệt'         => 'status-approved',
            'Đang giao hàng'   => 'status-shipping',
            'Đã giao hàng'     => 'status-delivered',
            'Đã hủy'           => 'status-cancelled',
            default            => 'status-default',
        };

    }

    public function showDetails($orderID)
    {
        $order = DB::selectOne(
            "SELECT o.*, 
                    cu.name AS customer_name, cu.email AS customer_email, cu.phone AS customer_phone,
                    ad.name AS admin_name,
                    s.statusValue,
                    p.payMethod
             FROM orders o
             LEFT JOIN users cu ON o.cusID = cu.id
             LEFT JOIN users ad ON o.adminID = ad.id
             LEFT JOIN status s ON o.staID = s.statusID
             LEFT JOIN payments p ON o.payID = p.paymentID
             WHERE o.orderID = ?",
            [$orderID]
        );
        if (!$order) {
            abort(404);
        }

        $details = DB::select(
            "SELECT od.*, pd.prdID, pd.sizeId, pd.colorId,
                    pr.productName, pr.productSellPrice,
                    s.sizeName, c.colorName,
                    img.imageLink AS firstImage
             FROM order_details od
             JOIN product_details pd ON od.productDetailID = pd.id
             JOIN products pr ON pd.prdID = pr.productID
             LEFT JOIN sizes s ON pd.sizeId = s.sizeId
             LEFT JOIN colors c ON pd.colorId = c.colorId
             LEFT JOIN (
                SELECT pi.prdID, pi.imageLink
                FROM product_images pi
                JOIN (
                    SELECT prdID, MIN(imageID) AS firstImageID
                    FROM product_images
                    GROUP BY prdID
                ) x ON pi.imageID = x.firstImageID
             ) img ON img.prdID = pr.productID
             WHERE od.orderID = ?",
            [$orderID]
        );

        $orderObj = new \stdClass();
        foreach ($order as $k => $v) {
            $orderObj->{$k} = $v;
        }
        $orderObj->customer = (object)[
            'name' => $order->customer_name,
            'email' => $order->customer_email,
            'phone' => $order->customer_phone,
        ];
        $orderObj->admin = $order->admin_name ? (object)['name' => $order->admin_name] : null;
        $orderObj->status = (object)['statusValue' => $order->statusValue];
        $orderObj->payment = (object)['payMethod' => $order->payMethod];
        // Không có quan hệ discount, đặt null để view không lỗi
        $orderObj->discount = null;

        $orderObj->orderDetails = collect($details)->map(function ($row) {
            $detail = new \stdClass();
            foreach ($row as $k => $v) {
                $detail->{$k} = $v;
            }
            $detail->productDetail = (object)[
                'product' => (object)[
                    'productName' => $row->productName,
                    'productSellPrice' => $row->productSellPrice,
                    'firstImage' => $row->firstImage ? (object)['imageLink' => $row->firstImage] : null,
                ],
                'size' => $row->sizeName ? (object)['sizeName' => $row->sizeName] : null,
                'color' => $row->colorName ? (object)['colorName' => $row->colorName] : null,
            ];
            return $detail;
        });

        return view('UserPage.orders-details', ['order' => $orderObj]);
    }

    public function delivered($orderID)
    {
        $cusID = Auth::id();
        DB::update(
            "UPDATE orders SET isPayment = 1, staID = 4, updated_at = ? WHERE orderID = ? AND cusID = ?",
            [now(), $orderID, $cusID]
        );

        return redirect()->route('orders.showOrders')->with('success', "Xác nhận thành công. Cảm ơn quý khách.");
    }

    public function cancel($orderID)
    {
        $cusID = Auth::id();

        $order = DB::selectOne(
            "SELECT * FROM orders WHERE orderID = ? AND cusID = ?",
            [$orderID, $cusID]
        );

        if (!$order) {
            return back()->with('error', 'Không tìm thấy đơn hàng hoặc bạn không có quyền hủy đơn này.');
        }

        if (!in_array($order->staID, [1, 2])) {
            return back()->with('error', 'Chỉ có thể hủy đơn hàng đang chờ xử lý.');
        }

        // Hoàn lại tồn kho
        $details = DB::select(
            "SELECT productDetailID, orderQuantity FROM order_details WHERE orderID = ?",
            [$orderID]
        );
        foreach ($details as $detail) {
            DB::update(
                "UPDATE product_details SET productQuantity = productQuantity + ?, updated_at = ? WHERE id = ?",
                [$detail->orderQuantity, now(), $detail->productDetailID]
            );
        }

        DB::update(
            "UPDATE orders SET staID = 5, updated_at = ? WHERE orderID = ?",
            [now(), $orderID]
        );

        return back()->with('success', "Đơn hàng #$orderID đã được hủy và hoàn lại kho.");
    }

    private function calculateDiscount($program, $amount)
    {
        $discount = 0;
        if (($program->discount_type ?? '') === 'percent') {
            $discount = $amount * (($program->discount_value ?? 0) / 100);
        } else {
            $discount = (float) ($program->discount_value ?? 0);
        }

        if (!empty($program->max_discount)) {
            $discount = min($discount, (float) $program->max_discount);
        }

        return $discount;
    }
}
