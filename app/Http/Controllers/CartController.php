<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Surfsidemedia\Shoppingcart\Facades\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Session\SessionUtils;

class CartController extends Controller
{
    public function index()
    {
        $items = Cart::instance('cart')->content();
        return view('cart', compact('items'));
    }

    // public function add_to_cart(Request $request)
    // {
    //     Cart::instance('cart')->add($request->id, $request->name, $request->quantity, $request->price)->associate('App\Models\Product');

    //     return redirect()->back();
    // }

    public function add_to_cart(Request $request)
    {
        // Thêm sản phẩm vào giỏ hàng
        Cart::instance('cart')->add($request->id, $request->name, $request->quantity, $request->price)->associate('App\Models\Product');

        // Nếu có mã giảm giá, tính toán lại giá trị khuyến mãi
        if (Session::has('coupon')) {
            $this->calculateDiscounts();
        }

        return redirect()->back();
    }


    // public function increase_cart_quantity($rowId)
    // {
    //     $product = Cart::instance('cart')->get($rowId);
    //     $qty = $product->qty + 1;
    //     Cart::instance('cart')->update($rowId, $qty);
    //     return redirect()->back();
    // }

    // public function increase_cart_quantity($rowId)
    // {
    //     $product = Cart::instance('cart')->get($rowId);
    //     $qty = $product->qty + 1;
    //     Cart::instance('cart')->update($rowId, $qty);

    //     // Tính toán lại tổng giá trị
    //     $this->setAmountforCheckout();

    //     return redirect()->back();
    // }

    public function increase_cart_quantity($rowId)
    {
        $product = Cart::instance('cart')->get($rowId);
        $qty = $product->qty + 1;
        Cart::instance('cart')->update($rowId, $qty);

        // Tính toán lại giá trị
        $this->setAmountforCheckout();
        if (Session::has('coupon')) {
            $this->calculateDiscounts();
        }

        return redirect()->back();
    }

    // public function decrease_cart_quantity($rowId)
    // {
    //     $product = Cart::instance('cart')->get($rowId);
    //     $qty = $product->qty - 1;
    //     Cart::instance('cart')->update($rowId, $qty);
    //     return redirect()->back();
    // }

    // public function decrease_cart_quantity($rowId)
    // {
    //     $product = Cart::instance('cart')->get($rowId);
    //     $qty = $product->qty - 1;

    //     // Đảm bảo số lượng không nhỏ hơn 1
    //     if ($qty < 1) {
    //         $this->remove_item($rowId); // Xóa sản phẩm nếu số lượng nhỏ hơn 1
    //     } else {
    //         Cart::instance('cart')->update($rowId, $qty);
    //     }

    //     // Tính toán lại tổng giá trị
    //     $this->setAmountforCheckout();

    //     return redirect()->back();
    // }

    public function decrease_cart_quantity($rowId)
    {
        $product = Cart::instance('cart')->get($rowId);
        $qty = $product->qty - 1;

        // Nếu số lượng nhỏ hơn 1, xóa sản phẩm
        if ($qty < 1) {
            $this->remove_item($rowId);
        } else {
            Cart::instance('cart')->update($rowId, $qty);
        }

        // Tính toán lại giá trị
        $this->setAmountforCheckout();
        if (Session::has('coupon')) {
            $this->calculateDiscounts();
        }

        return redirect()->back();
    }


    // public function remove_item($rowId)
    // {
    //     Cart::instance('cart')->remove($rowId);
    //     return redirect()->back();
    // }

    // public function remove_item($rowId)
    // {
    //     // Xóa sản phẩm khỏi giỏ hàng
    //     Cart::instance('cart')->remove($rowId);

    //     // Kiểm tra nếu có mã giảm giá
    //     if (Session::has('coupon')) {
    //         // Gọi lại phương thức tính toán discounts
    //         $this->calculateDiscounts();
    //     }

    //     return redirect()->back();
    // }

    // public function remove_item($rowId)
    // {
    //     // Xóa sản phẩm khỏi giỏ hàng
    //     Cart::instance('cart')->remove($rowId);

    //     // Tính toán lại tổng giá trị
    //     $this->setAmountforCheckout();

    //     return redirect()->back();
    // }

    public function remove_item($rowId)
    {
        Cart::instance('cart')->remove($rowId);

        // Tính toán lại tổng giá trị
        $this->setAmountforCheckout();
        if (Session::has('coupon')) {
            $this->calculateDiscounts();
        }

        return redirect()->back();
    }




    public function empty_cart()
    {
        Cart::instance('cart')->destroy();
        return redirect()->back();
    }

    public function apply_coupon_code(Request $request)
    {
        $coupon_code = $request->coupon_code;
        if (isset($coupon_code)) {
            $coupon = Coupon::where('code', $coupon_code)->where('expiry_date', '>=', Carbon::today())
                ->where('cart_value', '<=', Cart::instance('cart')->subtotal())->first();
            if (!$coupon) {
                return redirect()->back()->with('error', 'Invalid coupon code!');
            } else {
                Session::put('coupon', [
                    'code' => $coupon->code,
                    'type' => $coupon->type,
                    'value' => $coupon->value,
                    'cart_value' => $coupon->cart_value
                ]);
                $this->calculateDiscounts();
                return redirect()->back()->with('success', 'Coupon code has been applied!');
            }
        } else {
            return redirect()->back()->with('error', 'Invalid coupon code!');
        }
    }

    // public function calculateDiscounts()
    // {
    //     $discount = 0;
    //     if (session()->has('coupon')) {
    //         if (Session::get('coupon')['type'] == 'fixed') {
    //             $discount = session()->get('coupon')['value'];
    //         }
    //     } else {
    //         $discount = (Cart::instance('cart')->subtotal() * session()->get('coupon')['value']) / 100;
    //     }

    //     $subtotalAfterDiscount = Cart::instance('cart')->subtotal() - $discount;
    //     $taxAfterDiscount = ($subtotalAfterDiscount * config('cart.tax')) / 100;
    //     $totalAfterDiscount = $subtotalAfterDiscount + $taxAfterDiscount;

    //     Session::put('discounts', [
    //         'discount' => number_format(floatval($discount), 2, '.', ''),
    //         'subtotal' => number_format(floatval($subtotalAfterDiscount), 2, '.', ''),
    //         'tax' => number_format(floatval($taxAfterDiscount), 2, '.', ''),
    //         'total' => number_format(floatval($totalAfterDiscount), 2, '.', ''),
    //     ]);
    // }

    // public function calculateDiscounts()
    // {
    //     $discount = 0;

    //     // Kiểm tra nếu có mã giảm giá trong session
    //     if (session()->has('coupon')) {
    //         if (Session::get('coupon')['type'] == 'fixed') {
    //             $discount = floatval(Session::get('coupon')['value']);
    //         } else {
    //             $subtotal = floatval(str_replace(',', '', Cart::instance('cart')->subtotal()));
    //             $discount = ($subtotal * floatval(Session::get('coupon')['value'])) / 100;
    //         }
    //     }

    //     // Tính toán lại giá trị sau khi giảm giá
    //     $subtotal = floatval(str_replace(',', '', Cart::instance('cart')->subtotal()));
    //     $subtotalAfterDiscount = $subtotal - $discount;
    //     $taxAfterDiscount = ($subtotalAfterDiscount * config('cart.tax')) / 100;
    //     $totalAfterDiscount = $subtotalAfterDiscount + $taxAfterDiscount;

    //     // Lưu lại các giá trị vào session
    //     Session::put('discounts', [
    //         'discount' => number_format(floatval($discount), 2, '.', ''),
    //         'subtotal' => number_format(floatval($subtotalAfterDiscount), 2, '.', ''),
    //         'tax' => number_format(floatval($taxAfterDiscount), 2, '.', ''),
    //         'total' => number_format(floatval($totalAfterDiscount), 2, '.', ''),
    //     ]);
    // }

    public function calculateDiscounts()
    {
        $discount = 0;

        // Kiểm tra nếu có mã giảm giá trong session
        if (session()->has('coupon')) {
            $coupon = Session::get('coupon');
            if ($coupon['type'] === 'fixed') {
                $discount = floatval($coupon['value']);
            } else {
                $subtotal = floatval(str_replace(',', '', Cart::instance('cart')->subtotal()));
                $discount = ($subtotal * floatval($coupon['value'])) / 100;
            }
        }

        // Tính toán lại các giá trị
        $subtotal = floatval(str_replace(',', '', Cart::instance('cart')->subtotal()));
        $subtotalAfterDiscount = max($subtotal - $discount, 0);
        $taxRate = config('cart.tax', 15); // Thuế mặc định là 15%
        $taxAfterDiscount = ($subtotalAfterDiscount * $taxRate) / 100;
        $totalAfterDiscount = $subtotalAfterDiscount + $taxAfterDiscount;

        // Lưu lại các giá trị vào session 'checkout'
        Session::put('checkout', [
            'discount' => round($discount, 2),
            'subtotal' => round($subtotal, 2),
            'subtotalAfterDiscount' => round($subtotalAfterDiscount, 2),
            'tax' => round($taxAfterDiscount, 2),
            'total' => round($totalAfterDiscount, 2),
        ]);
    }




    // public function remove_coupon_code()
    // {
    //     Session::forget('coupon');
    //     Session::forget('discounts');
    //     return back()->with('success', 'Coupon has been removed!');
    // }

    public function remove_coupon_code()
    {
        // Xóa mã giảm giá khỏi session
        Session::forget('coupon');
        Session::forget('checkout'); // Xóa dữ liệu cũ của checkout để tính toán lại.

        // Tính toán lại giá trị giỏ hàng mà không có mã giảm giá
        $this->setAmountforCheckout();

        return back()->with('success', 'Coupon has been removed!');
    }


    public function checkout()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $address = Address::where('user_id', Auth::user()->id)->where('isdefault', 1)->first();
        return view('checkout', compact('address'));
    }

    // public function place_an_order(Request $request)
    // {
    //     $user_id = Auth::user()->id;
    //     $address = Address::where('user_id', $user_id)->where('isdefault', true)->first();
    //     if (!$address) {
    //         $request->validate([
    //             'name' => 'required|max:100',
    //             'phone' => 'required|numeric|digits:10',
    //             'zip' => 'required|numeric|digits:6',
    //             'state' => 'required',
    //             'city' => 'required',
    //             'address' => 'required',
    //             'locality' => 'required',
    //             'landmark' => 'required'
    //         ]);

    //         $address = new Address();
    //         $address->name = $request->name;
    //         $address->phone = $request->phone;
    //         $address->zip = $request->zip;
    //         $address->state = $request->state;
    //         $address->city = $request->city;
    //         $address->address = $request->address;
    //         $address->locality = $request->locality;
    //         $address->landmark = $request->landmark;
    //         $address->country = 'Vietnam';
    //         $address->user_id = $user_id;
    //         $address->isdefault = true;
    //         $address->save();
    //     }

    //     $this->setAmountforCheckout();

    //     $order = new Order();
    //     $order->user_id = $user_id;
    //     $order->subtotal = Session::get('checkout')['subtotal'];
    //     $order->discount = Session::get('checkout')['discount'];
    //     $order->tax = Session::get('checkout')['tax'];
    //     $order->total = Session::get('checkout')['total'];
    //     $order->name = $address->name;
    //     $order->phone = $address->phone;
    //     $order->locality = $address->locality;
    //     $order->address = $address->address;
    //     $order->city = $address->city;
    //     $order->state = $address->state;
    //     $order->country = $address->country;
    //     $order->landmark = $address->landmark;
    //     $order->zip = $address->zip;
    //     $order->save();

    //     foreach (Cart::instance('cart')->content() as $item) {
    //         $orderItem = new OrderItem();
    //         $orderItem->product_id = $item->id;
    //         $orderItem->order_id = $order->id;
    //         $orderItem->price = $item->price;
    //         $orderItem->quantity = $item->qty;
    //         $orderItem->save();
    //     }

    //     if ($request->mode == "card") {
    //         //
    //     } elseif ($request->mode == "paypal") {
    //         //
    //     } elseif ($request->mode == "cod") {
    //         $transaction = new Transaction();
    //         $transaction->user_id = $user_id;
    //         $transaction->order_id = $order->id;
    //         $transaction->mode = $request->mode;
    //         $transaction->status = "pending";
    //         $transaction->save();
    //     }

    //     Cart::instance('cart')->destroy();
    //     Session::forget('checkout');
    //     Session::forget('coupon');
    //     Session::forget('discounts');
    //     Session::put('order_id', $order->id);
    //     // return view('order-confirmation',compact('order'));
    //     return redirect()->route('cart.order.confirmation');
    // }
    // public function place_an_order(Request $request)
    // {
    //     $user_id = Auth::user()->id;

    //     // Lấy địa chỉ mặc định
    //     $address = Address::where('user_id', $user_id)->where('isdefault', true)->first();
    //     if (!$address) {
    //         $request->validate([
    //             'name' => 'required|max:100',
    //             'phone' => 'required|numeric|digits:10',
    //             'zip' => 'required|numeric|digits:6',
    //             'state' => 'required',
    //             'city' => 'required',
    //             'address' => 'required',
    //             'locality' => 'required',
    //             'landmark' => 'required',
    //         ]);

    //         $address = new Address();
    //         $address->name = $request->name;
    //         $address->phone = $request->phone;
    //         $address->zip = $request->zip;
    //         $address->state = $request->state;
    //         $address->city = $request->city;
    //         $address->address = $request->address;
    //         $address->locality = $request->locality;
    //         $address->landmark = $request->landmark;
    //         $address->country = 'Vietnam';
    //         $address->user_id = $user_id;
    //         $address->isdefault = true;
    //         $address->save();
    //     }

    //     // Thiết lập dữ liệu từ session
    //     $subtotal = floatval(str_replace(',', '', Session::get('checkout')['subtotal'] ?? 0));
    //     $discount = floatval(str_replace(',', '', Session::get('checkout')['discount'] ?? 0));
    //     $tax = floatval(str_replace(',', '', Session::get('checkout')['tax'] ?? 0));
    //     $total = floatval(str_replace(',', '', Session::get('checkout')['total'] ?? 0));

    //     // Lưu order
    //     $order = new Order();
    //     $order->user_id = $user_id;
    //     $order->subtotal = $subtotal;
    //     $order->discount = $discount;
    //     $order->tax = $tax;
    //     $order->total = $total;
    //     $order->name = $address->name;
    //     $order->phone = $address->phone;
    //     $order->locality = $address->locality;
    //     $order->address = $address->address;
    //     $order->city = $address->city;
    //     $order->state = $address->state;
    //     $order->country = $address->country;
    //     $order->landmark = $address->landmark;
    //     $order->zip = $address->zip;
    //     $order->save();

    //     // Lưu các sản phẩm trong giỏ hàng vào order items
    //     foreach (Cart::instance('cart')->content() as $item) {
    //         $orderItem = new OrderItem();
    //         $orderItem->product_id = $item->id;
    //         $orderItem->order_id = $order->id;
    //         $orderItem->price = $item->price;
    //         $orderItem->quantity = $item->qty;
    //         $orderItem->save();
    //     }

    //     // Xử lý thanh toán (nếu có)
    //     if ($request->mode == "cod") {
    //         $transaction = new Transaction();
    //         $transaction->user_id = $user_id;
    //         $transaction->order_id = $order->id;
    //         $transaction->mode = $request->mode;
    //         $transaction->status = "pending";
    //         $transaction->save();
    //     }

    //     // Xóa giỏ hàng và session
    //     Cart::instance('cart')->destroy();
    //     Session::forget('checkout');
    //     Session::forget('coupon');
    //     Session::forget('discounts');

    //     // Chuyển hướng
    //     Session::put('order_id', $order->id);
    //     return redirect()->route('cart.order.confirmation');
    // }

    public function place_an_order(Request $request)
    {
        // Xác nhận người dùng đã đăng nhập
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please log in to place your order.');
        }

        $user_id = Auth::user()->id;

        // Kiểm tra và lấy dữ liệu từ session 'checkout'
        if (!Session::has('checkout')) {
            return redirect()->back()->with('error', 'Checkout session is missing. Please try again.');
        }

        $checkoutData = Session::get('checkout');
        $subtotal = floatval($checkoutData['subtotal'] ?? 0);
        $discount = floatval($checkoutData['discount'] ?? 0);
        $tax = floatval($checkoutData['tax'] ?? 0);
        $total = floatval($checkoutData['total'] ?? 0);

        // Kiểm tra tính hợp lệ của dữ liệu
        if ($subtotal <= 0 || $total <= 0) {
            return redirect()->back()->with('error', 'Invalid order amount. Please check your cart.');
        }

        // Nếu không có địa chỉ, yêu cầu người dùng cung cấp thông tin
        if ($request->has('name') && $request->has('phone') && $request->has('zip') && $request->has('state') && $request->has('city') && $request->has('address') && $request->has('locality')) {
            $request->validate([
                'name' => 'required|max:100',
                'phone' => 'required|numeric|digits:10',
                'zip' => 'required|numeric|digits:6',
                'state' => 'required',
                'city' => 'required',
                'address' => 'required',
                'locality' => 'required',
                'landmark' => 'nullable',
            ]);

            // Lưu địa chỉ mới
            $address = new Address();
            $address->name = $request->name;
            $address->phone = $request->phone;
            $address->zip = $request->zip;
            $address->state = $request->state;
            $address->city = $request->city;
            $address->address = $request->address;
            $address->locality = $request->locality;
            $address->landmark = $request->landmark;
            $address->country = 'Vietnam'; // Giá trị mặc định
            $address->user_id = $user_id;
            $address->isdefault = true;
            $address->save();
        } else {
            // Nếu không có địa chỉ, trả về lỗi
            return redirect()->back()->with('error', 'Please provide your shipping details.');
        }

        // Lưu thông tin đơn hàng vào bảng orders
        $order = new Order();
        $order->user_id = $user_id;
        $order->subtotal = $subtotal;
        $order->discount = $discount;
        $order->tax = $tax;
        $order->total = $total;
        $order->name = $address->name;
        $order->phone = $address->phone;
        $order->locality = $address->locality;
        $order->address = $address->address;
        $order->city = $address->city;
        $order->state = $address->state;
        $order->country = $address->country;
        $order->landmark = $address->landmark;
        $order->zip = $address->zip;
        $order->save();

        // Lưu thông tin các sản phẩm trong giỏ hàng vào bảng order_items
        foreach (Cart::instance('cart')->content() as $item) {
            $orderItem = new OrderItem();
            $orderItem->product_id = $item->id;
            $orderItem->order_id = $order->id;
            $orderItem->price = $item->price;
            $orderItem->quantity = $item->qty;
            $orderItem->save();
        }

        // Lưu thông tin giao dịch (transaction)
        if ($request->mode === 'cod') {
            $transaction = new Transaction();
            $transaction->user_id = $user_id;
            $transaction->order_id = $order->id;
            $transaction->mode = $request->mode;
            $transaction->status = "pending"; // Mặc định trạng thái ban đầu
            $transaction->save();
        }

        // Dọn sạch giỏ hàng và session sau khi đặt hàng thành công
        Cart::instance('cart')->destroy();
        Session::forget('checkout');
        Session::forget('coupon');
        Session::forget('discounts');

        // Lưu order_id vào session để hiển thị trang xác nhận
        Session::put('order_id', $order->id);

        // Chuyển hướng đến trang xác nhận đơn hàng
        return redirect()->route('cart.order.confirmation')->with('success', 'Your order has been placed successfully!');
    }




    // public function setAmountforCheckout()
    // {
    //     if (!Cart::instance('cart')->content()->count() > 0) {
    //         Session::forget('checkout');
    //         return;
    //     }

    //     if (Session::has('coupon')) {
    //         Session::put('checkout', [
    //             'discount' => Session::get('discounts')['discount'],
    //             'subtotal' => Session::get('discounts')['subtotal'],
    //             'tax' => Session::get('discounts')['tax'],
    //             'total' => Session::get('discounts')['total'],
    //         ]);
    //     } else {
    //         Session::put('checkout', [
    //             'discount' => 0,
    //             'subtotal' => Cart::instance('cart')->subtotal(),
    //             'tax' => Cart::instance('cart')->tax(),
    //             'total' => Cart::instance('cart')->total(),
    //         ]);
    //     }
    // }
    // public function setAmountforCheckout()
    // {
    //     if (!Cart::instance('cart')->content()->count() > 0) {
    //         Session::forget('checkout');
    //         return;
    //     }

    //     if (Session::has('coupon')) {
    //         Session::put('checkout', [
    //             'discount' => floatval(str_replace(',', '', Session::get('discounts')['discount'] ?? 0)),
    //             'subtotal' => floatval(str_replace(',', '', Session::get('discounts')['subtotal'] ?? 0)),
    //             'tax' => floatval(str_replace(',', '', Session::get('discounts')['tax'] ?? 0)),
    //             'total' => floatval(str_replace(',', '', Session::get('discounts')['total'] ?? 0)),
    //         ]);
    //     } else {
    //         Session::put('checkout', [
    //             'discount' => 0,
    //             'subtotal' => floatval(str_replace(',', '', Cart::instance('cart')->subtotal())),
    //             'tax' => floatval(str_replace(',', '', Cart::instance('cart')->tax())),
    //             'total' => floatval(str_replace(',', '', Cart::instance('cart')->total())),
    //         ]);
    //     }
    // }
    // public function setAmountforCheckout()
    // {
    //     // Kiểm tra nếu giỏ hàng rỗng
    //     if (!Cart::instance('cart')->content()->count() > 0) {
    //         Session::forget('checkout');
    //         return;
    //     }

    //     // Tính tổng phụ (subtotal)
    //     $subtotal = floatval(str_replace(',', '', Cart::instance('cart')->subtotal()));

    //     // Kiểm tra nếu có mã giảm giá
    //     if (Session::has('coupon')) {
    //         $coupon = Session::get('coupon');
    //         $discount = 0;

    //         // Tính giá trị giảm giá
    //         if ($coupon['type'] == 'fixed') {
    //             $discount = floatval($coupon['value']); // Giảm giá cố định
    //         } else {
    //             $discount = ($subtotal * floatval($coupon['value'])) / 100; // Giảm giá theo phần trăm
    //         }

    //         // Tính subtotal sau giảm giá
    //         $subtotalAfterDiscount = max($subtotal - $discount, 0);

    //         // Tính thuế
    //         $taxRate = config('cart.tax') ?? 10; // Lấy thuế từ config (mặc định 10%)
    //         $taxAfterDiscount = ($subtotalAfterDiscount * $taxRate) / 100;

    //         // Tổng cộng sau giảm giá
    //         $totalAfterDiscount = $subtotalAfterDiscount + $taxAfterDiscount;

    //         // Lưu vào session
    //         Session::put('checkout', [
    //             'discount' => round($discount, 2),
    //             'subtotal' => round($subtotal, 2), // Tổng phụ không giảm giá
    //             'subtotalAfterDiscount' => round($subtotalAfterDiscount, 2), // Tổng sau giảm giá
    //             'tax' => round($taxAfterDiscount, 2),
    //             'total' => round($totalAfterDiscount, 2),
    //         ]);
    //     } else {
    //         // Không có mã giảm giá, tính toán bình thường
    //         $taxRate = config('cart.tax') ?? 10;
    //         $tax = ($subtotal * $taxRate) / 100;
    //         $total = $subtotal + $tax;

    //         // Lưu vào session
    //         Session::put('checkout', [
    //             'discount' => 0,
    //             'subtotal' => round($subtotal, 2),
    //             'subtotalAfterDiscount' => round($subtotal, 2), // Không giảm giá thì bằng subtotal
    //             'tax' => round($tax, 2),
    //             'total' => round($total, 2),
    //         ]);
    //     }
    // }

    // public function setAmountforCheckout()
    // {
    //     if (!Cart::instance('cart')->content()->count() > 0) {
    //         Session::forget('checkout');
    //         return;
    //     }

    //     $subtotal = floatval(str_replace(',', '', Cart::instance('cart')->subtotal()));

    //     // Nếu có mã giảm giá
    //     if (Session::has('coupon')) {
    //         $coupon = Session::get('coupon');
    //         $discount = 0;

    //         // Tính giá trị giảm giá
    //         if ($coupon['type'] == 'fixed') {
    //             $discount = floatval($coupon['value']);
    //         } else {
    //             $discount = ($subtotal * floatval($coupon['value'])) / 100;
    //         }

    //         $subtotalAfterDiscount = max($subtotal - $discount, 0);

    //         $taxRate = config('cart.tax') ?? 15; // Mặc định thuế 15%
    //         $taxAfterDiscount = ($subtotalAfterDiscount * $taxRate) / 100;

    //         $totalAfterDiscount = $subtotalAfterDiscount + $taxAfterDiscount;

    //         // Lưu vào session
    //         Session::put('checkout', [
    //             'discount' => round($discount, 2),
    //             'subtotal' => round($subtotal, 2),
    //             'subtotalAfterDiscount' => round($subtotalAfterDiscount, 2),
    //             'tax' => round($taxAfterDiscount, 2),
    //             'total' => round($totalAfterDiscount, 2),
    //         ]);
    //     } else {
    //         // Không có mã giảm giá
    //         $taxRate = config('cart.tax') ?? 15; // Mặc định thuế 15%
    //         $tax = ($subtotal * $taxRate) / 100;
    //         $total = $subtotal + $tax;

    //         // Lưu vào session
    //         Session::put('checkout', [
    //             'discount' => 0,
    //             'subtotal' => round($subtotal, 2),
    //             'subtotalAfterDiscount' => round($subtotal, 2),
    //             'tax' => round($tax, 2),
    //             'total' => round($total, 2),
    //         ]);
    //     }
    // }

    public function setAmountforCheckout()
    {
        if (!Cart::instance('cart')->content()->count() > 0) {
            Session::forget('checkout');
            return;
        }

        $subtotal = floatval(str_replace(',', '', Cart::instance('cart')->subtotal()));

        $taxRate = config('cart.tax') ?? 15; // Thuế mặc định là 15%
        $tax = ($subtotal * $taxRate) / 100;
        $total = $subtotal + $tax;

        // Lưu vào session 'checkout'
        Session::put('checkout', [
            'discount' => 0, // Không có giảm giá
            'subtotal' => round($subtotal, 2),
            'subtotalAfterDiscount' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'total' => round($total, 2),
        ]);
    }





    public function order_confirmation()
    {
        if (Session::has('order_id')) {
            $order = Order::find(Session::get('order_id'));
            return view('order-confirmation', compact('order'));
        }
        return redirect()->route('cart.index');
    }
}
