#!/bin/bash
TOKEN="9390|0JnMect5gBKT0NeBhk0lLgSG95Jj8ZxUuGJSIPDVdb6bfcfd"
ENV_ID="env-a2ac7a89-dbf5-43aa-ac95-ca88d3065873"

CMD="php artisan tinker --execute='echo App\\\\Models\\\\User::whereEmail(\"prodverify.demo-lakeview-junior@demo.klassapp.test\")->value(\"school_id\");'"

curl -s -X POST "https://cloud.laravel.com/api/environments/${ENV_ID}/commands" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d "{\"command\":\"${CMD}\"}"
