#!/bin/bash

set -e

# Stop RabbitMQ service
sudo systemctl stop rabbitmq-server || true
sudo systemctl disable rabbitmq-server || true

# Uninstall RabbitMQ and Erlang
sudo apt-get remove --purge -y rabbitmq-server erlang* || true
sudo apt-get autoremove -y || true

# Remove RabbitMQ data and configuration
sudo rm -rf /var/lib/rabbitmq
sudo rm -rf /var/log/rabbitmq
sudo rm -rf /etc/rabbitmq

# Remove RabbitMQ user and group
sudo deluser rabbitmq || true
sudo delgroup rabbitmq || true

# Remove apt repository configuration
sudo rm -f /etc/apt/sources.list.d/rabbitmq.list
sudo rm -f /usr/share/keyrings/rabbitmq*.gpg
sudo rm -f /usr/share/keyrings/com.rabbitmq.team.gpg

# Update package cache
sudo apt-get update

echo "RabbitMQ uninstallation completed successfully"