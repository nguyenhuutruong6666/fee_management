<?php
session_start();
include("../config/db.php");

$vnp_HashSecret = "GLOD1KF7WG0VYZPDQUFZ5SL3S0FL9OA1";

$inputData = [];
foreach ($_GET as $key => $value) {
    if (strpos($key, "vnp_") === 0) {
        $inputData[$key] = $value;
    }
}

$secureHash = $inputData['vnp_SecureHash'] ?? '';
unset($inputData['vnp_SecureHash']);

ksort($inputData);

$hashData = "";
$i = 0;
foreach ($inputData as $key => $value) {
    if ($i) $hashData .= '&' . $key . "=" . $value;
    else { $hashData .= $key . "=" . $value; $i = 1; }
}

$check = hash_hmac('sha512', $hashData, $vnp_HashSecret);

$transaction_code = $inputData['vnp_TxnRef'] ?? '';
$responseCode     = $inputData['vnp_ResponseCode'] ?? '';

if ($check === $secureHash) {

    if ($responseCode == "00") {

        // cập nhật DB
        $conn->query("UPDATE fee_payment SET status='Success'
                      WHERE transaction_code='$transaction_code'");

        $conn->query("
            UPDATE fee_obligation 
            SET status='Đã nộp'
            WHERE id = (SELECT obligation_id FROM fee_payment WHERE transaction_code='$transaction_code')
        ");

        echo "<h2 style='color:green'>✔ Thanh toán thành công!</h2>";
        echo "<p>Mã GD: $transaction_code</p>";

    } else {
        echo "<h2 style='color:red'>❌ Thanh toán thất bại!</h2>";
    }

} else {
    echo "<h2 style='color:red'>Sai chữ ký bảo mật!</h2>";
}
