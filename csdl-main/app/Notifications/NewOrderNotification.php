<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

class NewOrderNotification extends Notification
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
            ->subject('Đơn hàng mới từ khách hàng')
            ->greeting('Xin chào Admin,')
            ->line('Bạn vừa nhận được một đơn hàng mới.')
            ->line('Mã đơn hàng: #' . $this->order->orderID)
            ->line('Tổng tiền: ' . number_format($this->order->totalPrice) . ' VND')
            ->line('Khách hàng: ' . $this->order->customer->name)
            ->line('Số điện thoại: ' . $this->order->orderPhoneNumber)
            ->line('Địa chỉ: ' . $this->order->shipping_street . ', ' . $this->order->shipping_ward . ', ' . $this->order->shipping_district . ', ' . $this->order->shipping_city);

        foreach ($this->order->orderDetails as $detail) {
            $product = $detail->productDetail->product;

            $mailMessage->line(new HtmlString('<hr>'));
            $mailMessage->line('Sản phẩm: ' . $product->productName);
            $mailMessage->line('Số lượng: ' . $detail->orderQuantity);

        }

        $mailMessage->action('Xem chi tiết đơn hàng', url('/orders/' . $this->order->orderID));
        $mailMessage->line('Cảm ơn bạn đã sử dụng hệ thống.');

        return $mailMessage;
    }

}
