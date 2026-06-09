#!/bin/bash
set -e

# ====================================
# KlassApp Evolution API Provisioning Script
# Droplet: klassapp-evo — 46.101.130.70 (1 GB / 25 GB)
# ====================================

EVOLUTION_AUTH_KEY=$(openssl rand -hex 32)
INSTANCE_NAME="klassapp"
APP_DOMAIN="klassapp.xyz"
APP_PUBLIC_IP="165.245.250.16"

echo "========================================"
echo " KlassApp Evolution API Provisioning"
echo " Instance: $INSTANCE_NAME"
echo "========================================"

# ── 1. System Updates ──────────────────────
echo "[1/8] Updating system packages..."
apt update && apt upgrade -y

# ── 2. Install Docker ──────────────────────
echo "[2/8] Installing Docker..."
apt install -y docker.io docker-compose-v2
systemctl enable --now docker

# ── 3. Create Evolution API Stack ────────────────────
echo "[3/8] Creating Evolution API stack..."
mkdir -p /opt/evolution

cat > /opt/evolution/docker-compose.yml << 'DOCKERCOMPOSE'
version: "3.8"
services:
  evolution-api:
    image: evolutionapi/evolution-api:latest
    ports:
      - "8081:8081"
    environment:
      AUTHENTICATION_API_KEY: "${EVOLUTION_AUTH_KEY}"
      DATABASE_ENABLED: "true"
      DATABASE_CONNECTION_URI: "mongodb://mongo:27017/evolution"
    restart: unless-stopped
    depends_on:
      mongo:
        condition: service_started
    networks:
      - evolution-net

  mongo:
    image: mongo:6
    volumes:
      - mongo-data:/data/db
    restart: unless-stopped
    command: ["mongod", "--wiredTigerCacheSizeGB", "0.2"]
    networks:
      - evolution-net

volumes:
  mongo-data:

networks:
  evolution-net:
DOCKERCOMPOSE

cat > /opt/evolution/.env << ENVFILE
EVOLUTION_AUTH_KEY=${EVOLUTION_AUTH_KEY}
ENVFILE

# ── 4. Start Services ──────────────────────
echo "[4/8] Starting Evolution API + MongoDB..."
cd /opt/evolution
docker compose up -d

# Wait for services to be ready
echo "Waiting for services to start..."
sleep 15

# ── 5. Create Instance ─────────────────────
echo "[5/8] Creating Evolution API instance..."

CREATE_RESPONSE=$(curl -s -X POST http://localhost:8081/instance/create \
  -H "apikey: ${EVOLUTION_AUTH_KEY}" \
  -H "Content-Type: application/json" \
  -d "{\"instanceName\": \"${INSTANCE_NAME}\", \"qrcode\": true}")

echo "Create instance response: $CREATE_RESPONSE"

# Extract API key
INSTANCE_API_KEY=$(echo "$CREATE_RESPONSE" | grep -o '"apiKey":"[^"]*"' | cut -d'"' -f4)

if [ -z "$INSTANCE_API_KEY" ]; then
    echo "WARNING: Could not auto-extract instance API key."
    echo "Raw response saved. Run manually:"
    echo "  curl http://localhost:8081/instance/connectionState/${INSTANCE_NAME} -H 'apikey: ${EVOLUTION_AUTH_KEY}'"
    INSTANCE_API_KEY="MANUAL_EXTRACTION_REQUIRED"
else
    echo "Instance API key: $INSTANCE_API_KEY"
fi

# ── 6. Get QR Code ──────────────────
echo "[6/8] Fetching QR code..."
QR_RESPONSE=$(curl -s "http://localhost:8081/instance/qrcode/${INSTANCE_NAME}" \
  -H "apikey: ${EVOLUTION_AUTH_KEY}")

echo ""
echo "========================================"
echo " SCAN THE QR CODE NOW"
echo "========================================"
echo "QR Response: $QR_RESPONSE"
echo ""
echo "If the response contains a base64 image, extract and decode it."
echo "If it contains a URL, open the URL and scan with WhatsApp."
echo ""
echo "WhatsApp → Settings → Linked Devices → Link a Device"
echo "========================================"

# ── 7. Configure UFW Firewall ────────────────────
echo "[7/8] Configuring firewall..."
ufw default deny incoming
ufw default allow outgoing
ufw allow 22
ufw allow from 10.19.0.5 to any port 8081
ufw --force enable

# ── 8. Display Info ─────────────────
echo "[8/8] Setup complete!"

echo ""
echo "========================================"
echo " Evolution API Provisioning Complete!"
echo "========================================"
echo ""
echo " Evolution API Key:  ${EVOLUTION_AUTH_KEY}"
echo " Instance API Key:   ${INSTANCE_API_KEY}"
echo " Instance Name:      ${INSTANCE_NAME}"
echo " Internal URL:       http://10.19.0.6:8081"
echo ""
echo " Next Steps:"
echo " 1. Scan the QR code above to connect WhatsApp"
echo " 2. Verify connection:"
echo "    curl http://localhost:8081/instance/connectionState/${INSTANCE_NAME} -H 'apikey: ${EVOLUTION_AUTH_KEY}'"
echo ""
echo " 3. Configure webhooks AFTER scanning QR:"
echo ""
echo "   curl -X POST http://localhost:8081/webhook/set/${INSTANCE_NAME} \\"
echo "     -H 'apikey: ${EVOLUTION_AUTH_KEY}' \\"
echo "     -H 'Content-Type: application/json' \\"
echo "     -d '{"
echo "       \"webhookUrl\": \"https://${APP_DOMAIN}/api/whatsapp/inbound\","
echo "       \"webhookEvents\": [\"messages.upsert\"],"
echo "       \"webhookHeaders\": {\"apikey\": \"${EVOLUTION_AUTH_KEY}\"}"
echo "     }'"
echo ""
echo "   curl -X POST http://localhost:8081/webhook/set/${INSTANCE_NAME} \\"
echo "     -H 'apikey: ${EVOLUTION_AUTH_KEY}' \\"
echo "     -H 'Content-Type: application/json' \\"
echo "     -d '{"
echo "       \"webhookUrl\": \"https://${APP_DOMAIN}/api/whatsapp/delivery\","
echo "       \"webhookEvents\": [\"messages.ack\", \"messages.error\"],"
echo "       \"webhookHeaders\": {\"apikey\": \"${EVOLUTION_AUTH_KEY}\"}"
echo "     }'"
echo ""
echo " 4. Update the app server .env with:"
echo "    EVOLUTION_API_KEY=${INSTANCE_API_KEY}"
echo "    Then run: php artisan config:cache"
echo "========================================"

# Save credentials
cat > /root/klassapp-evo-credentials.txt << CREDS
=================================
KlassApp Evolution API Credentials — $(date)
=================================
Evolution Auth Key:   ${EVOLUTION_AUTH_KEY}
Instance API Key:     ${INSTANCE_API_KEY}
Instance Name:        ${INSTANCE_NAME}
Internal URL:         http://10.19.0.6:8081
App Webhook URL:      https://${APP_DOMAIN}/api/whatsapp/inbound
Delivery Webhook:     https://${APP_DOMAIN}/api/whatsapp/delivery
=================================
CREDS

chmod 600 /root/klassapp-evo-credentials.txt
echo "Credentials saved to /root/klassapp-evo-credentials.txt"
