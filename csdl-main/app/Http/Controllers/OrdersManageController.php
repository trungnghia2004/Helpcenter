<?php

namespace App\Http\Controllers;

use App\Notifications\NewOrderNotification;
use App\Notifications\OrderConfirmationForCustomer;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

class OrdersManageController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('statusID');
        $perPage = 10;
        $page = max((int) $request->input('page', 1), 1);
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $bindings = [];
        if (!empty($status)) {
            $where .= ' AND o.staID = ?';
            $bindings[] = $status;
        }
        if (!empty($search)) {
            $where .= ' AND (o.orderID LIKE ? OR u.phone LIKE ?)';
            $bindings[] = '%' . $search . '%';
            $bindings[] = '%' . $search . '%';
        }

        $totalRow = DB::selectOne("SELECT COUNT(*) AS aggregate FROM orders o LEFT JOIN users u ON o.cusID = u.id WHERE $where", $bindings);
        $total = $totalRow ? (int) $totalRow->aggregate : 0;

        $ordersRows = DB::select(
            "SELECT o.*, s.statusValue, u.name AS customer_name, u.phone AS customer_phone
             FROM orders o
             LEFT JOIN status s ON o.staID = s.statusID
             LEFT JOIN users u ON o.cusID = u.id
             WHERE $where
             ORDER BY o.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($bindings, [$perPage, $offset])
        );

        $orders = $this->mapOrdersWithDetails($ordersRows);

        $ordersPaginator = new LengthAwarePaginator(
            $orders,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $payments = collect(DB::select("SELECT * FROM payments"));
        $products = collect(DB::select(
            "SELECT p.*, 
                    (SELECT imageLink FROM product_images WHERE prdID = p.productID ORDER BY imageID ASC LIMIT 1) AS firstImage
             FROM products p
             WHERE p.isDeleted = 0"
        ));
        $statusCountsRows = DB::select("SELECT staID, COUNT(*) AS total FROM orders GROUP BY staID");
        $statusCounts = [];
        foreach ($statusCountsRows as $row) {
            $statusCounts[$row->staID] = $row->total;
        }
        $totalOrders = $total;

        return view('AdminPage.Orders', [
            'orders' => $ordersPaginator,
            'total' => $total,
            'payments' => $payments,
            'products' => $products,
            'statusCounts' => $statusCounts,
            'totalOrders' => $totalOrders
        ]);
    }

    public function store(Request $request)
    {
        if ($request->phone) {
            $length = strlen($request->phone);
            if ($length > 10) {
                return back()->with('error', 'Số điện thoại không được vượt quá 10 ký tự!');
            }
        }

        $customer = DB::selectOne(
            "SELECT * FROM users WHERE phone = ? AND role = 'customer' AND isDeleted = 0 LIMIT 1",
            [$request->phone]
        );

        $now = now();
        if ($customer) {
            DB::insert(
                "INSERT INTO orders (cusID, adminID, orderPhoneNumber, shipping_street, shipping_city, shipping_district, shipping_ward, payID, staID, totalPrice, created_at, updated_at, isPayment)
                 VALUES (?, NULL, ?, NULL, NULL, NULL, NULL, ?, 1, ?, ?, ?, 0)",
                [
                    $customer->id,
                    $request->phone,
                    $request->payID,
                    $request->total ?? 0,
                    $now,
                    $now,
                ]
            );
        } else {
            DB::insert(
                "INSERT INTO users (username, name, email, phone, role, password, isDeleted, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 'customer', ?, 0, ?, ?)",
                [
                    'user_' . time(),
                    $request->nameCus ?? 'Khách hàng',
                    'user' . time() . '@example.com',
                    $request->phone,
                    Hash::make('123456'),
                    $now,
                    $now,
                ]
            );
            $newUserId = DB::getPdo()->lastInsertId();

            DB::insert(
                "INSERT INTO orders (cusID, adminID, orderPhoneNumber, shipping_street, shipping_city, shipping_district, shipping_ward, payID, staID, totalPrice, created_at, updated_at, isPayment)
                 VALUES (?, NULL, ?, NULL, NULL, NULL, NULL, ?, 1, ?, ?, ?, 0)",
                [
                    $newUserId,
                    $request->phone,
                    $request->payID,
                    $request->total ?? 0,
                    $now,
                    $now,
                ]
            );
        }

        return redirect()->route('order-manage.index')->with('success', 'Đơn hàng đã được tạo thành công bởi quản trị viên!');
    }

    public function approve(Request $request, $orderID)
    {
        $cusID = $request->input('cusID');
        DB::update(
            "UPDATE orders SET staID = 2, updated_at = ? WHERE orderID = ? AND cusID = ?",
            [now(), $orderID, $cusID]
        );

        return back()->with('success', "Đơn hàng của khách $cusID đã được duyệt.");
    }

    public function deliver(Request $request, $orderID)
    {
        $cusID = $request->input('cusID');
        DB::update(
            "UPDATE orders SET staID = 3, shipping_code = ?, updated_at = ? WHERE orderID = ? AND cusID = ?",
            [$request->shipping_code, now(), $orderID, $cusID]
        );

        return back()->with('success', "Đơn hàng của khách $cusID đã được gửi cho bên giao hàng.");
    }

    public function cancel(Request $request, $orderID)
    {
        $cusID = $request->input('cusID');
        $order = DB::selectOne(
            "SELECT * FROM orders WHERE orderID = ? AND cusID = ?",
            [$orderID, $cusID]
        );

        if (!$order) {
            return back()->with('error', "Không tìm thấy đơn hàng #$orderID của khách $cusID.");
        }

        if (!in_array($order->staID, [1, 2])) {
            return back()->with('error', "Chỉ có thể hủy đơn hàng đang chờ xử lý hoặc chuẩn bị.");
        }

        $details = DB::select("SELECT productDetailID, orderQuantity FROM order_details WHERE orderID = ?", [$orderID]);
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

        return back()->with('success', "Đơn hàng #$orderID của khách $cusID đã được hủy và hoàn lại kho.");
    }

    public function getSizes($productID)
    {
        $sizes = DB::select(
            "SELECT DISTINCT pd.sizeId, s.sizeName
             FROM product_details pd
             JOIN sizes s ON pd.sizeId = s.sizeId
             WHERE pd.prdID = ? AND pd.productQuantity > 0",
            [$productID]
        );
        $formatted = collect($sizes)->map(fn($row) => [
            'sizeId' => $row->sizeId,
            'size' => $row->sizeName,
        ])->values();

        return response()->json($formatted);
    }

    public function getColors($productID, $sizeId)
    {
        $colors = DB::select(
            "SELECT DISTINCT c.colorId, c.colorName
             FROM product_details pd
             JOIN colors c ON pd.colorId = c.colorId
             WHERE pd.prdID = ? AND pd.sizeId = ? AND pd.isDeleted = 0",
            [$productID, $sizeId]
        );
        $data = collect($colors)->map(fn($row) => [
            'colorId' => $row->colorId,
            'color' => $row->colorName,
        ]);

        return response()->json($data);
    }

    public function showDetails($id)
    {
        $orderRow = DB::selectOne(
            "SELECT o.*, cu.name AS customer_name, cu.email AS customer_email, cu.phone AS customer_phone,
                    ad.name AS admin_name,
                    s.statusValue,
                    p.payMethod
             FROM orders o
             LEFT JOIN users cu ON o.cusID = cu.id
             LEFT JOIN users ad ON o.adminID = ad.id
             LEFT JOIN status s ON o.staID = s.statusID
             LEFT JOIN payments p ON o.payID = p.paymentID
             WHERE o.orderID = ?",
            [$id]
        );
        if (!$orderRow) {
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
            [$id]
        );

        $order = $this->mapSingleOrder($orderRow, $details);
        // Không có quan hệ discount, đặt null để view không lỗi
        $order->discount = null;

        $products = collect(DB::select(
            "SELECT p.*, c.categoryName,
                    (SELECT imageLink FROM product_images WHERE prdID = p.productID ORDER BY imageID ASC LIMIT 1) AS firstImage
             FROM products p
             JOIN categories c ON p.cateID = c.categoryID
             WHERE p.isDeleted = 0 AND c.isDeleted = 0"
        ));

        return view('AdminPage.OrderDetails', compact('order', 'products'));
    }

    public function addMoreDetails(Request $request, $id)
    {
        $products = $request->input('products');
        $grouped = array_chunk($products, 5);

        $totalPrice = 0;
        $detailsToAdd = [];

        foreach ($grouped as $productGroup) {
            $productID = $productGroup[1]['prdID'] ?? null;
            $sizeID = $productGroup[2]['sizeId'] ?? null;
            $colorID = $productGroup[3]['colorId'] ?? null;
            $quantity = (int) ($productGroup[4]['quantity'] ?? 1);

            if (!$productID || !$sizeID || !$colorID) {
                continue;
            }

            $productDetail = DB::selectOne(
                "SELECT * FROM product_details WHERE prdID = ? AND sizeId = ? AND colorId = ?",
                [$productID, $sizeID, $colorID]
            );
            if (!$productDetail) {
                continue;
            }

            $product = DB::selectOne("SELECT productSellPrice FROM products WHERE productID = ?", [$productID]);
            if (!$product) {
                continue;
            }

            $unitPrice = $product->productSellPrice;
            $totalPrice += $unitPrice * $quantity;

            $key = $productDetail->id;
            if (!isset($detailsToAdd[$key])) {
                $detailsToAdd[$key] = [
                    'productDetailID' => $productDetail->id,
                    'quantity' => 0,
                    'unitPrice' => $unitPrice,
                ];
            }
            $detailsToAdd[$key]['quantity'] += $quantity;
            $detailsToAdd[$key]['unitPrice'] = $unitPrice;
        }

        DB::beginTransaction();
        try {
            $now = now();
            foreach ($detailsToAdd as $detail) {
                $existing = DB::selectOne(
                    'SELECT orderQuantity FROM order_details WHERE orderID = ? AND productDetailID = ?',
                    [$id, $detail['productDetailID']]
                );

                if ($existing) {
                    DB::update(
                        'UPDATE order_details
                         SET orderQuantity = orderQuantity + ?, unitPrice = ?, updated_at = ?
                         WHERE orderID = ? AND productDetailID = ?',
                        [
                            $detail['quantity'],
                            $detail['unitPrice'],
                            $now,
                            $id,
                            $detail['productDetailID'],
                        ]
                    );
                } else {
                    DB::insert(
                        'INSERT INTO order_details (orderID, productDetailID, orderQuantity, unitPrice, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?)',
                        [
                            $id,
                            $detail['productDetailID'],
                            $detail['quantity'],
                            $detail['unitPrice'],
                            $now,
                            $now,
                        ]
                    );
                }

                DB::update(
                    "UPDATE product_details SET productQuantity = GREATEST(productQuantity - ?, 0), updated_at = ? WHERE id = ?",
                    [$detail['quantity'], $now, $detail['productDetailID']]
                );
            }

            DB::update(
                "UPDATE orders SET totalPrice = ?, updated_at = ? WHERE orderID = ?",
                [$totalPrice, $now, $id]
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $orderModel = \App\Models\Order::find($id);
        $admin = DB::selectOne("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
        if ($admin && $admin->email && $orderModel) {
            Notification::route('mail', $admin->email)->notify(new NewOrderNotification($orderModel));
        }
        if ($orderModel) {
            $customerEmail = DB::selectOne("SELECT email FROM users WHERE id = ?", [$orderModel->cusID]);
            if ($customerEmail && $customerEmail->email) {
                Notification::route('mail', $customerEmail->email)->notify(new OrderConfirmationForCustomer($orderModel));
            }
        }

        return redirect()->back()->with('success', 'Đã thêm chi tiết đơn hàng và cập nhật tổng tiền.');
    }

    public function filterOrders(Request $request)
    {
        $statusParam = $request->input('status', 'all');
        $searchQuery = $request->input('search');
        $perPage = 10;
        $page = max((int) $request->input('page', 1), 1);
        $offset = ($page - 1) * $perPage;

        $statusRows = DB::select('SELECT staID, statusValue FROM status');
        $statuses = [];
        foreach ($statusRows as $row) {
            $statuses[$row->statusValue] = $row->staID;
        }

        $where = '1=1';
        $bindings = [];
        if ($statusParam !== 'all') {
            $dbStatusValue = '';
            switch ($statusParam) {
                case 'pending':
                    $dbStatusValue = 'Đang chờ duyệt';
                    break;
                case 'approved':
                    $dbStatusValue = 'Đã duyệt';
                    break;
                case 'delivering':
                    $dbStatusValue = 'Đang giao hàng';
                    break;
                case 'delivered':
                    $dbStatusValue = 'Đã giao hàng';
                    break;
                case 'cancelled':
                    $dbStatusValue = 'Đã hủy';
                    break;
            }
            if (!empty($dbStatusValue) && isset($statuses[$dbStatusValue])) {
                $where .= ' AND o.staID = ?';
                $bindings[] = $statuses[$dbStatusValue];
            }
        }
        if ($searchQuery) {
            $where .= ' AND o.orderID LIKE ?';
            $bindings[] = '%' . $searchQuery . '%';
        }

        $totalRow = DB::selectOne("SELECT COUNT(*) AS aggregate FROM orders o WHERE $where", $bindings);
        $total = $totalRow ? (int) $totalRow->aggregate : 0;

        $orders = DB::select(
            "SELECT o.*, s.statusValue
             FROM orders o
             LEFT JOIN status s ON o.staID = s.staID
             WHERE $where
             ORDER BY o.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($bindings, [$perPage, $offset])
        );

        $counts = [];
        $allOrdersCount = DB::selectOne("SELECT COUNT(*) AS total FROM orders")->total ?? 0;
        $counts['all'] = $allOrdersCount;
        foreach ($statuses as $statusName => $staID) {
            $counts[$this->slugifyStatusName($statusName)] = DB::selectOne(
                "SELECT COUNT(*) AS total FROM orders WHERE staID = ?",
                [$staID]
            )->total ?? 0;
        }

        return response()->json([
            'orders' => $orders,
            'counts' => $counts,
            'total' => $total,
        ]);
    }

    private function mapOrdersWithDetails(array $ordersRows)
    {
        $orderIds = array_map(fn($o) => $o->orderID, $ordersRows);
        $detailsByOrder = [];
        if ($orderIds) {
            $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
            $details = DB::select(
                "SELECT od.*, pd.prdID, pr.productName, pr.productSellPrice,
                        img.imageLink AS firstImage
                 FROM order_details od
                 JOIN product_details pd ON od.productDetailID = pd.id
                 JOIN products pr ON pd.prdID = pr.productID
                 LEFT JOIN (
                    SELECT pi.prdID, pi.imageLink
                    FROM product_images pi
                    JOIN (
                        SELECT prdID, MIN(imageID) AS firstImageID
                        FROM product_images
                        GROUP BY prdID
                    ) x ON pi.imageID = x.firstImageID
                 ) img ON img.prdID = pr.productID
                 WHERE od.orderID IN ($placeholders)",
                $orderIds
            );
            foreach ($details as $d) {
                $detailsByOrder[$d->orderID][] = $d;
            }
        }

        $orders = [];
        foreach ($ordersRows as $row) {
            $order = new \stdClass();
            foreach ($row as $k => $v) {
                $order->{$k} = $v;
            }
            // convert created_at string -> Carbon for view formatting
            $order->created_at = \Illuminate\Support\Carbon::parse($order->created_at);
            $order->customer = (object)[
                'name' => $row->customer_name ?? null,
                'phone' => $row->customer_phone ?? null,
            ];
            $order->status = (object)[
                'statusValue' => $row->statusValue ?? null,
            ];
            $order->orderDetails = collect($detailsByOrder[$row->orderID] ?? [])->map(function ($d) {
                $detail = new \stdClass();
                $detail->orderID = $d->orderID;
                $detail->productDetailID = $d->productDetailID;
                $detail->orderQuantity = $d->orderQuantity;
                $detail->unitPrice = $d->unitPrice;
                $detail->productDetail = (object)[
                    'product' => (object)[
                        'productName' => $d->productName,
                        'productSellPrice' => $d->productSellPrice,
                        'firstImage' => $d->firstImage ? (object)['imageLink' => $d->firstImage] : null,
                    ],
                ];
                return $detail;
            });
            $orders[] = $order;
        }
        return $orders;
    }

    private function mapSingleOrder($orderRow, $detailsRows)
    {
        $order = new \stdClass();
        foreach ($orderRow as $k => $v) {
            $order->{$k} = $v;
        }
        if (!empty($order->created_at)) {
            $order->created_at = \Illuminate\Support\Carbon::parse($order->created_at);
        }
        if (!empty($order->updated_at)) {
            $order->updated_at = \Illuminate\Support\Carbon::parse($order->updated_at);
        }
        $order->customer = (object)[
            'name' => $orderRow->customer_name,
            'email' => $orderRow->customer_email,
            'phone' => $orderRow->customer_phone,
        ];
        $order->admin = $orderRow->admin_name ? (object)['name' => $orderRow->admin_name] : null;
        $order->status = (object)['statusValue' => $orderRow->statusValue];
        $order->payment = (object)['payMethod' => $orderRow->payMethod];

        $order->orderDetails = collect($detailsRows)->map(function ($d) {
            $detail = new \stdClass();
            foreach ($d as $k => $v) {
                $detail->{$k} = $v;
            }
            $detail->productDetail = (object)[
                'product' => (object)[
                    'productName' => $d->productName,
                    'productSellPrice' => $d->productSellPrice,
                    'firstImage' => $d->firstImage ? (object)['imageLink' => $d->firstImage] : null,
                ],
                'size' => $d->sizeName ? (object)['sizeName' => $d->sizeName] : null,
                'color' => $d->colorName ? (object)['colorName' => $d->colorName] : null,
            ];
            return $detail;
        });

        return $order;
    }

    private function slugifyStatusName($statusName)
    {
        return str_replace('-', '', \Illuminate\Support\Str::slug($statusName));
    }
}
