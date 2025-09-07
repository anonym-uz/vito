#!/bin/bash

set -e

# Install prerequisites
sudo apt-get update
sudo apt-get install -y curl gnupg apt-transport-https

# Add RabbitMQ signing keys
curl -1sLf "https://keys.openpgp.org/vks/v1/by-fingerprint/0A9AF2115F4687BD29803A206B73A36E6026DFCA" | sudo gpg --dearmor | sudo tee /usr/share/keyrings/com.rabbitmq.team.gpg > /dev/null
curl -1sLf "https://github.com/rabbitmq/signing-keys/releases/download/3.0/cloudsmith.rabbitmq-erlang.E495BB49CC4BBE5B.key" | sudo gpg --dearmor | sudo tee /usr/share/keyrings/rabbitmq.E495BB49CC4BBE5B.gpg > /dev/null
curl -1sLf "https://github.com/rabbitmq/signing-keys/releases/download/3.0/cloudsmith.rabbitmq-server.9F4587F226208342.key" | sudo gpg --dearmor | sudo tee /usr/share/keyrings/rabbitmq.9F4587F226208342.gpg > /dev/null

# Add apt repositories
sudo tee /etc/apt/sources.list.d/rabbitmq.list <<EOF
## Provides modern Erlang/OTP releases
deb [arch=amd64,arm64 signed-by=/usr/share/keyrings/rabbitmq.E495BB49CC4BBE5B.gpg] https://ppa1.rabbitmq.com/rabbitmq/rabbitmq-erlang/deb/ubuntu $(lsb_release -cs) main
deb-src [signed-by=/usr/share/keyrings/rabbitmq.E495BB49CC4BBE5B.gpg] https://ppa1.rabbitmq.com/rabbitmq/rabbitmq-erlang/deb/ubuntu $(lsb_release -cs) main

# Provides RabbitMQ
deb [arch=amd64,arm64 signed-by=/usr/share/keyrings/rabbitmq.9F4587F226208342.gpg] https://ppa1.rabbitmq.com/rabbitmq/rabbitmq-server/deb/ubuntu $(lsb_release -cs) main
deb-src [signed-by=/usr/share/keyrings/rabbitmq.9F4587F226208342.gpg] https://ppa1.rabbitmq.com/rabbitmq/rabbitmq-server/deb/ubuntu $(lsb_release -cs) main
EOF

# Update package indices
sudo apt-get update -y

# Install Erlang packages
sudo apt-get install -y erlang-base \
                        erlang-asn1 erlang-crypto erlang-eldap erlang-ftp erlang-inets \
                        erlang-mnesia erlang-os-mon erlang-parsetools erlang-public-key \
                        erlang-runtime-tools erlang-snmp erlang-ssl \
                        erlang-syntax-tools erlang-tftp erlang-tools erlang-xmerl

# Install RabbitMQ server
sudo apt-get install -y rabbitmq-server

# Enable and start RabbitMQ
sudo systemctl enable rabbitmq-server
sudo systemctl start rabbitmq-server

# Wait for RabbitMQ to fully start
sleep 5

# Set up default admin user with provided credentials
sudo rabbitmqctl add_user "{{ $username }}" "{{ $password }}"
sudo rabbitmqctl set_user_tags "{{ $username }}" administrator
sudo rabbitmqctl set_permissions -p / "{{ $username }}" ".*" ".*" ".*"

echo "RabbitMQ installation completed successfully"