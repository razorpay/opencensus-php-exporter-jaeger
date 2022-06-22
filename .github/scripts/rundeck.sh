#!/bin/sh
# Triggers rundeck's webhook to execute e2e tests.

AUTHZ_COMMIT=`curl https://authz-admin-concierge.razorpay.com/commit.txt 2>/dev/null`
CREDCASE_COMMIT=`curl https://credcase.razorpay.com/commit.txt  2>/dev/null`
AUTH_OUTBOX_RELAY_COMMIT=`curl https://auth-outbox-relay.concierge.razorpay.com/commit.txt 2>/dev/null`
curl -c /tmp/cookies -o -s -w "%{http_code}" --location --request GET 'https://deploy-api.razorpay.com/login' --header "Authorization: Bearer ${GIT_TOKEN}"
cookies="$(cat /tmp/cookies | awk '/SESSION/ { print $NF }')"
headerCookie="Cookie: SESSION=$cookies"
EDGE_COMMIT=`curl --location --request GET "https://deploy-api.razorpay.com/executions?pipelineConfigIds=${EDGE_PIPELINE_ID}&statuses=SUCCEEDED&limit=1" -H "${headerCookie}" 2>/dev/null | jq '.[0].trigger.parameters.commit_id'`

# Arguments
AUTHSERVICE_COMMIT=$1

# Variables
# These services with their corresponding images will be braught up on devstack for testing.
SERVICES="edge"
IMAGES=$EDGE_COMMIT
RUNDECK_WEBHOOK_URL="https://rundeck.dev.razorpay.in/api/40/webhook/89Gs7PNHMppaTplotgy5FtZERC7TlGFI"

DATA='{
    "triggered_by": "auth-service",
    "kube_manifests_ref": "master",
    "self": {
        "name": "edge",
        "commit_id": '"$EDGE_COMMIT"',
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
                    "image": "'"$AUTH_OUTBOX_RELAY_COMMIT"'"
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
