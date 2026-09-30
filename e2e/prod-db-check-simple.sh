#!/bin/bash
TOKEN="9390|0JnMect5gBKT0NeBhk0lLgSG95Jj8ZxUuGJSIPDVdb6bfcfd"
ENV_ID="env-a2ac7a89-dbf5-43aa-ac95-ca88d3065873"

# Single-line tinker command to inspect the synthetic fixtures
CMD="php artisan tinker --execute='print_r([\"admin_school_id\"=>DB::table(\"users\")->where(\"email\",\"prodverify.demo-lakeview-junior@demo.klassapp.test\")->value(\"school_id\"), \"sl307\"=>DB::table(\"standards_link\")->where(\"id\",307)->first()?->toArray(), \"ay53\"=>DB::table(\"academic_years\")->where(\"school_id\",53)->where(\"status\",1)->orderByDesc(\"id\")->first()?->toArray(), \"students307\"=>DB::table(\"student_academics\")->where(\"standardLink_id\",307)->count()]);'"

curl -s -X POST "https://cloud.laravel.com/api/environments/${ENV_ID}/commands" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d "{\"command\":\"${CMD}\"}"
