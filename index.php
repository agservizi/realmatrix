<?php
// Simple front controller to redirect root requests into backend/public
http_response_code(302);
header('Location: backend/public/');
exit;
