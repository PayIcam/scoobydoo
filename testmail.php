<?php

echo "tu es sur la bonne page de tester de mail";

// Le message
$message = "Ceci est un test !";

// Dans le cas où nos lignes comportent plus de 70 caractères, nous les coupons en utilisant wordwrap()
$message = wordwrap($message, 70, "\r\n");

// Envoi du mail
mail('ch.delep@gmail.com', 'Mon Sujet', $message);
?>