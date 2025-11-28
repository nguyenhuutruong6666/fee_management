<?php
require_once("../config/db.php");

$vnp_HashSecret = "GLOD1KF7WG0VYZPDQUFZ5SL3S0FL9OA1";

$inputData = [];
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}

$vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
unset($inputData['vnp_SecureHash']);

ksort($inputData);

$hashData = "";
$i = 0;
foreach ($inputData as $key => $value) {
    if ($i) $hashData .= '&' . $key . "=" . $value;
    else { $hashData .= $key . "=" . $value; $i = 1; }
}

$secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

$returnData = ["RspCode" => "99", "Message" => "Unknown"];

if ($secureHash === $vnp_SecureHash) {

    $orderId      = $inputData['vnp_TxnRef'];
    $vnp_Response = $inputData['vnp_ResponseCode'];

    $result = $conn->query("SELECT * FROM fee_payment WHERE transaction_code='$orderId' LIMIT 1");

    if ($result->num_rows == 1) {

        $payment = $result->fetch_assoc();

        if ($payment['status'] != 'Success') {

            if ($vnp_Response == "00") {

                $conn->query("UPDATE fee_payment SET status='Success' 
                              WHERE transaction_code='$orderId'");

                $conn->query("
                    UPDATE fee_obligation
                    SET status='Đã nộp'
                    WHERE id = (SELECT obligation_id FROM fee_payment WHERE transaction_code='$orderId')
                ");

                $returnData = ["RspCode" => "00", "Message" => "Confirm Success"];

            } else {

                $conn->query("UPDATE fee_payment SET status='Failed'
                              WHERE transaction_code='$orderId'");

                $returnData = ["RspCode" => "00", "Message" => "Payment Failed"];
            }

        } else {
            $returnData = ["RspCode" => "02", "Message" => "Already Confirmed"];
        }

    } else {
        $returnData = ["RspCode" => "01", "Message" => "Order Not Found"];
    }

} else {
    $returnData = ["RspCode" => "97", "Message" => "Invalid Signature"];
}

header('Content-Type: application/json');
echo json_encode($returnData);
