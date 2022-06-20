#!/bin/sh
# Triggers rundeck's webhook to execute e2e tests.

# Arguments
AUTHSERVICE_COMMIT=$1
AUTHZ_COMMIT=$2
CREDCASE_COMMIT=$3
EDGE_COMMIT=$4
OUTBOX_COMMIT=$5

# Variables
# These services with their corresponding images will be braught up on devstack for testing.
SERVICES="edge"
IMAGES=$EDGE_COMMIT
RUNDECK_WEBHOOK_URL="https://rundeck.dev.razorpay.in/api/40/webhook/89Gs7PNHMppaTplotgy5FtZERC7TlGFI"

DATA='{
    "triggered_by": "edge",
    "kube_manifest_ref": "master",
    "self": {
        "name": "edge",
        "commit_id": "'"$EDGE_COMMIT"'",
        "repository_name": "razorpay/edge",
        "e2e_pkgs": "./e2e/...",
        "e2e_flag_run": "",
        "e2e_flag_suite_method": "",
        "chart_values": {
            "create_mock_upstream": true,
            "e2e_execution": true
        }
    },
    "dependencies": [
        {
            "name": "authz",
            "commit_id": "'"$AUTHZ_COMMIT"'",
            "chart_values": {
                "ephemeral_db": true
            }
        },
        {
            "name": "credcase",
            "commit_id": "'"$CREDCASE_COMMIT"'",
            "chart_values": {
                "ephemeral_db": true
            }
        },
        {
            "name": "auth",
            "commit_id": "'"$AUTHSERVICE_COMMIT"'",
            "chart_values": {
                "ephemeral_db": true,
                "auth-outbox-relay": {
                    "ttl": "{{ .Values.ttl }}",
                    "devstack_label": "{{ .Values.devstack_label }}",
                    "secret": "{{ .Values.secret }}",
                    "ephemeral_db": true,
                    "image": "'"$OUTBOX_COMMIT"'"
                }
            }
        }
    ]
}'

echo $DATA

# Makes request.
RESPONSE=$(
    curl -X POST -s -i \
        -H "Accept: application/json" \
        -H "Content-Type: application/json" \
        -d "$DATA" \
        $RUNDECK_WEBHOOK_URL
)

echo "$RESPONSE"

# Returns 0 if response code is 200.
echo "$RESPONSE" | grep -q "^HTTP/1.1 200"
