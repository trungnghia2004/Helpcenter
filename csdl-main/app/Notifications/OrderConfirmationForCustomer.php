<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

class OrderConfirmationForCustomer extends Notification
{
    use Queueable;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $mailMessage = (new MailMessage)
            ->subject('Xác nhận đơn hàng #' . $this->order->orderID)
            ->greeting('Xin chào ' . $this->order->customer->name . ',')
            ->line('Cảm ơn bạn đã đặt hàng tại Trendy Teen Shop!')
            ->line('Mã đơn hàng: #' . $this->order->orderID)
            ->line('Tổng tiền: ' . number_format($this->order->totalPrice) . ' VND')
            ->line('Địa chỉ giao hàng: ' . $this->order->shipping_street . ', ' . $this->order->shipping_ward . ', ' . $this->order->shipping_district . ', ' . $this->order->shipping_city);

        foreach ($this->order->orderDetails as $detail) {
            $product = $detail->productDetail->product;

            $mailMessage->line(new HtmlString('<hr>'));
            $mailMessage->line('Sản phẩm: ' . $product->productName);
            $mailMessage->line('Số lượng: ' . $detail->orderQuantity);
        }

        $mailMessage->line('Chúng tôi sẽ sớm xử lý và giao hàng cho bạn.');
        $mailMessage->line('Cảm ơn bạn đã mua sắm!');

        return $mailMessage;
    }
}
