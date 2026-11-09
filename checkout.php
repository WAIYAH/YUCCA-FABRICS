<?php
require_once __DIR__ . '/includes/functions.php';
$items = cart_items();
if (!$items) { flash('error', 'Your bag is empty.'); redirect('shop.php'); }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $address = trim((string) ($_POST['address_line'] ?? ''));
    $city = trim((string) ($_POST['city'] ?? ''));
    $county = trim((string) ($_POST['county'] ?? ''));
    $postal = trim((string) ($_POST['postal_code'] ?? ''));
    $instructions = trim((string) ($_POST['delivery_instructions'] ?? ''));
    $method = (string) ($_POST['payment_method'] ?? '');
    if ($name === '' || mb_strlen($name)>150 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email)>190 || $phone === '' || strlen($phone)>40 || $address === '' || mb_strlen($address)>255 || $city === '' || mb_strlen($city)>100 || !in_array($method, ['cod','mpesa','bank_transfer'], true)) $errors[] = 'Please complete all required checkout details.';
    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $freshItems = [];
            $subtotal = 0.0;
            foreach ($items as $item) {
                $lock = $pdo->prepare('SELECT id,name,sku,price,sale_price,stock,unit FROM products WHERE id=? AND is_active=1 FOR UPDATE');
                $lock->execute([$item['id']]);
                $product = $lock->fetch();
                if (!$product || (int) $product['stock'] < (int) $item['quantity']) {
                    throw new RuntimeException('A fabric in your bag no longer has enough stock. Please review your bag.');
                }
                $unitPrice = $product['sale_price'] !== null ? (float) $product['sale_price'] : (float) $product['price'];
                $lineTotal = $unitPrice * (int) $item['quantity'];
                $subtotal += $lineTotal;
                $freshItems[] = $product + ['quantity' => (int) $item['quantity'], 'unit_price' => $unitPrice, 'line_total' => $lineTotal];
            }
            $userId = current_user()['id'] ?? null;
            $coupon = !empty($_SESSION['coupon']) ? coupon_for_checkout((string)$_SESSION['coupon'], $subtotal, $userId ? (int)$userId : null, true) : null;
            if (!empty($_SESSION['coupon']) && !$coupon) throw new RuntimeException('Your coupon is no longer available for this order. Remove it from your bag and try again.');
            $discount = $coupon['discount_amount'] ?? 0.0;
            $delivery = (float) DELIVERY_FEE;
            $tax = round(($subtotal - $discount) * TAX_RATE, 2);
            $total = $subtotal - $discount + $delivery + $tax;
            $orderNumber = 'YF' . date('ymd') . strtoupper(bin2hex(random_bytes(4)));
            $order = $pdo->prepare("INSERT INTO orders (user_id,order_number,customer_name,email,phone,address_line,city,county,postal_code,delivery_instructions,subtotal,discount,tax,delivery_fee,total,payment_method,payment_status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pending')");
            $order->execute([$userId,$orderNumber,$name,$email,$phone,$address,$city,$county ?: null,$postal ?: null,$instructions ?: null,$subtotal,$discount,$tax,$delivery,$total,$method]);
            $orderId = (int) $pdo->lastInsertId();
            $line = $pdo->prepare('INSERT INTO order_items (order_id,product_id,product_name,sku,unit_price,quantity,line_total) VALUES (?,?,?,?,?,?,?)');
            $stock = $pdo->prepare('UPDATE products SET stock=stock-? WHERE id=? AND stock>=?');
            foreach ($freshItems as $item) {
                $line->execute([$orderId,$item['id'],$item['name'],$item['sku'],$item['unit_price'],$item['quantity'],$item['line_total']]);
                $stock->execute([$item['quantity'],$item['id'],$item['quantity']]);
                if ($stock->rowCount() !== 1) throw new RuntimeException('Stock changed while placing your order. Please try again.');
            }
            $paymentStatus = 'pending';
            $pdo->prepare('INSERT INTO payments (order_id,method,amount,status) VALUES (?,?,?,?)')->execute([$orderId,$method,$total,$paymentStatus]);
            if ($coupon) $pdo->prepare('INSERT INTO coupon_usage (coupon_id,order_id,user_id) VALUES (?,?,?)')->execute([$coupon['id'],$orderId,$userId]);
            if ($userId) {
                $pdo->prepare('DELETE ci FROM cart_items ci JOIN cart c ON c.id=ci.cart_id WHERE c.user_id=?')->execute([$userId]);
            }
            $pdo->commit();
            $_SESSION['cart'] = [];
            unset($_SESSION['coupon']);
            $_SESSION['last_order'] = ['number' => $orderNumber, 'id' => $orderId];
            send_business_email($email, 'Yucca Fabrics order ' . $orderNumber, "Hello {$name},\n\nThank you for your order. We have received order {$orderNumber} for " . money($total) . ". We will contact you with delivery and payment updates.\n\nYucca Fabrics");
            redirect('order-success.php');
        } catch (RuntimeException $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = $exception->getMessage();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }
}
$pageTitle = 'Checkout | Yucca Fabrics';
require __DIR__ . '/includes/header.php';
?>
<section class="mx-auto max-w-7xl px-5 py-12"><p class="eyebrow">Almost yours</p><h1 class="mt-3 font-serif text-4xl">Delivery & payment</h1><?php foreach($errors as $error): ?><p class="mt-5 rounded bg-red-50 p-3 text-sm text-red-800"><?= e($error) ?></p><?php endforeach; ?>
<form method="post" class="mt-8 grid gap-8 lg:grid-cols-[1fr_360px]"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="grid content-start gap-4 bg-white p-6 sm:grid-cols-2"><h2 class="font-serif text-2xl sm:col-span-2">Delivery details</h2><label class="text-sm sm:col-span-2">Full name<input class="form-control mt-1" name="name" required value="<?= e($_POST['name'] ?? current_user()['name'] ?? '') ?>"></label><label class="text-sm">Email<input class="form-control mt-1" type="email" name="email" required value="<?= e($_POST['email'] ?? current_user()['email'] ?? '') ?>"></label><label class="text-sm">Phone<input class="form-control mt-1" type="tel" name="phone" required value="<?= e($_POST['phone'] ?? '') ?>"></label><label class="text-sm sm:col-span-2">Address<input class="form-control mt-1" name="address_line" required value="<?= e($_POST['address_line'] ?? '') ?>"></label><label class="text-sm">Town / City<input class="form-control mt-1" name="city" required value="<?= e($_POST['city'] ?? '') ?>"></label><label class="text-sm">County<input class="form-control mt-1" name="county" value="<?= e($_POST['county'] ?? '') ?>"></label><label class="text-sm">Postal code<input class="form-control mt-1" name="postal_code" value="<?= e($_POST['postal_code'] ?? '') ?>"></label><label class="text-sm sm:col-span-2">Delivery instructions<textarea class="form-control mt-1" name="delivery_instructions" rows="2"><?= e($_POST['delivery_instructions'] ?? '') ?></textarea></label>
<h2 class="mt-5 font-serif text-2xl sm:col-span-2">Payment method</h2><?php foreach(['cod'=>'Cash on delivery','mpesa'=>'M-Pesa','bank_transfer'=>'Bank transfer'] as $value=>$label): ?><label class="flex items-center gap-3 border border-black/10 p-4 text-sm sm:col-span-2"><input type="radio" name="payment_method" value="<?= e($value) ?>" <?= ($_POST['payment_method'] ?? 'mpesa')===$value?'checked':'' ?> required><span><strong><?= e($label) ?></strong><?php if($value==='mpesa'): ?><span class="block text-xs text-gray-500">Secure payment request after placing your order</span><?php elseif($value==='bank_transfer'): ?><span class="block text-xs text-gray-500">Bank details and reference instructions will be shown after order placement</span><?php endif; ?></span></label><?php endforeach; ?>
</div><aside class="h-fit bg-white p-6"><h2 class="font-serif text-2xl">Order summary</h2><?php $subtotal=array_sum(array_column($items,'line_total'));$summaryCoupon=!empty($_SESSION['coupon'])?coupon_for_checkout((string)$_SESSION['coupon'],$subtotal,current_user()?(int)current_user()['id']:null):null;$summaryDiscount=$summaryCoupon['discount_amount']??0.0;$tax=round(($subtotal-$summaryDiscount)*TAX_RATE,2);$total=$subtotal-$summaryDiscount+$tax+DELIVERY_FEE; ?><div class="mt-5 space-y-3 text-sm"><div class="flex justify-between"><span>Items (<?= cart_count() ?>)</span><span><?= e(money($subtotal)) ?></span></div><?php if($summaryDiscount>0): ?><div class="flex justify-between"><span>Coupon</span><span>−<?= e(money($summaryDiscount)) ?></span></div><?php elseif(!empty($_SESSION['coupon'])): ?><p class="text-xs text-red-700">Your coupon is no longer available and must be removed before ordering.</p><?php endif; ?><div class="flex justify-between"><span>Tax</span><span><?= e(money($tax)) ?></span></div><div class="flex justify-between"><span>Delivery</span><span><?= e(money(DELIVERY_FEE)) ?></span></div><div class="flex justify-between border-t border-black/10 pt-4 text-base font-semibold"><span>Total</span><span><?= e(money($total)) ?></span></div></div><button class="mt-6 w-full rounded-full bg-forest px-6 py-3 text-sm font-semibold text-white">Place order</button><p class="mt-4 text-xs leading-5 text-gray-500">Your final total is calculated securely on our server. We never store card details.</p></aside></form></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
