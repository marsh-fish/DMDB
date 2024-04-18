<?php
function showToastOnLoad($message, $type) {
    echo "<script>document.addEventListener('DOMContentLoaded', function() { showToast('". addslashes($message) ."', '". addslashes($type) ."'); });</script>";
}

function toastURL($url, $message, $type) {
    return $url . (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . "toast_message=" . rawurlencode($message) . "&toast_type=" . $type;
}
?>