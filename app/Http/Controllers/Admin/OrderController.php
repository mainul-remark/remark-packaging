<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CartonStatus;
use App\Http\Controllers\Controller;
use App\Models\Carton;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    /** Orders are imported (SAP later), so this page only lists and inspects them. */
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $search = $request->string('q')->trim()->toString();

        $counts = Order::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $orders = Order::query()->with(['customer', 'destination'])->withCount('items')->withSum('items as required_units', 'required_quantity')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search): void {
                $inner->where('order_number', 'like', "%{$search}%")->orWhere('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"));
            }))
            ->orderBy('priority')->orderByDesc('order_date')->orderByDesc('id')->paginate(20)->withQueryString();

        $packed = OrderItem::packedQuantities(OrderItem::query()->whereIn('order_id', $orders->pluck('id'))->pluck('id')->all());
        $packedByOrder = OrderItem::query()->whereIn('order_id', $orders->pluck('id'))->get(['id', 'order_id'])
            ->groupBy('order_id')->map(fn ($items): float => (float) $items->sum(fn ($item): float => $packed[$item->id] ?? 0));

        return view('admin.orders.index', [
            'orders' => $orders, 'counts' => $counts, 'activeStatus' => $status, 'packedByOrder' => $packedByOrder,
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['customer', 'destination', 'items.product']);
        $packed = OrderItem::packedQuantities($order->items->pluck('id')->all());
        $cartons = Carton::query()->where('order_id', $order->id)->where('status', '!=', CartonStatus::CANCELLED->value)->orderBy('id')->get();

        return view('admin.orders.show', ['order' => $order, 'packed' => $packed, 'cartons' => $cartons]);
    }
}
