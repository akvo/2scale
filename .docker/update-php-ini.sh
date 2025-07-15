#!/bin/bash

echo "Setting PHP timeout to 10 minutes..."

cat <<EOF > /usr/local/etc/php/conf.d/timeout.ini
max_execution_time = 600
max_input_time = 600
memory_limit = 512M
EOF

echo "PHP timeout settings applied."
