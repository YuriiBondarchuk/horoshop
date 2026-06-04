#!/usr/bin/env bash
set -e

KEY_DIR="$(dirname "$0")/../config/jwt"
mkdir -p "$KEY_DIR"

echo "Створюємо RSA ключі в $KEY_DIR"

# Задайте пароль для приватного ключа
read -s -p "Введіть passphrase для приватного ключа JWT: " PASSPHRASE
echo
export PASSPHRASE

openssl genpkey -algorithm RSA -out "$KEY_DIR/private.pem" -pkeyopt rsa_keygen_bits:4096 -aes256 -pass pass:"$PASSPHRASE"
openssl rsa -in "$KEY_DIR/private.pem" -out "$KEY_DIR/private_no_pass.pem" -passin pass:"$PASSPHRASE"
mv "$KEY_DIR/private_no_pass.pem" "$KEY_DIR/private.pem"
chmod 600 "$KEY_DIR/private.pem"

openssl rsa -in "$KEY_DIR/private.pem" -pubout -out "$KEY_DIR/public.pem"

echo "Ключі згенеровано"
echo "Додайте в .env: JWT_PASSPHRASE=$PASSPHRASE"
