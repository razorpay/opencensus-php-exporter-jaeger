#!/bin/sh
# Triggers argo's webhook to execute edge e2e tests.

GITHUB_TOKEN=${GITHUB_TOKEN}
SPINNAKER_TOKEN=${SPINNAKER_TOKEN}
# Arguments
AUTHSERVICE_COMMIT=$1
ARGO_TOKEN=$2

# Variables
# These services with their corresponding images will be brought up on devstack for testing.
CREDCASE_COMMIT=`curl https://credcase.razorpay.com/commit.txt  2>/dev/null`
AUTHZ_COMMIT=`curl https://authz-admin-concierge.razorpay.com/commit.txt 2>/dev/null`
AUTH_OUTBOX_RELAY_COMMIT=`curl https://auth-outbox-relay.concierge.razorpay.com/commit.txt 2>/dev/null`

curl -c /tmp/cookies -o -s -w "%{http_code}" --location --request GET 'https://deploy-api.razorpay.com/login' --header "Authorization: Bearer ${SPINNAKER_TOKEN}"
cookies="$(cat /tmp/cookies | awk '/SESSION/ { print $NF }')"
headerCookie="Cookie: SESSION=$cookies"
EDGE_COMMIT=`curl --location --request GET "https://deploy-api.razorpay.com/executions?pipelineConfigIds=${EDGE_PIPELINE_ID}&statuses=SUCCEEDED&limit=1" -H "${headerCookie}" 2>/dev/null | jq '.[0].trigger.parameters.commit_id'`

TERRAFORM_COMMIT=$(curl -L -X GET 'https://api.github.com/repos/razorpay/terraform-kong/commits?sha=master' -H 'Authorization: token '$GITHUB_TOKEN'' | jq '.[0].sha')
MOCK_GATEWAY_COMMIT=$(curl -L -X GET 'https://api.github.com/repos/razorpay/mock-gateway/commits?sha=master' -H 'Authorization: token '$GITHUB_TOKEN'' | jq '.[0].sha')

ARGO_WEBHOOK_URL="https://argo.dev.razorpay.in/api/v1/events/argo-workflows/edge"

DATA='{
    "kube_manifests_ref": "master",
    "edge_commit_id": '$EDGE_COMMIT',
    "self": {
        "name": "auth",
        "commit_id": "'"$AUTHSERVICE_COMMIT"'",
        "repository_name": "razorpay/auth-service",
        "chart_values": {
            "ephemeral_db": true,
            "web_requests_cpu" : "200m",
            "auth_replicas" : 2,
            "auth_node_selector" : "node.kubernetes.io/worker-bvt-devstack-graviton",
            "auth-outbox-relay": {
                "ttl": "{{ .Values.ttl }}",
                "devstack_label": "{{ .Values.devstack_label }}",
                "secret": "{{ .Values.secret }}",
                "ephemeral_db": true,
                "image": "'"$AUTH_OUTBOX_RELAY_COMMIT"'"
            }
        }
    },
    "dependencies": [
        {
            "name": "credcase",
            "commit_id": "'"$CREDCASE_COMMIT"'",
            "chart_values": {
              "ephemeral_db": true,
              "web_requests_cpu" : "100m",
              "credcase_replicas" : 2
            }
        },
        {
            "name": "edge",
            "commit_id": '$EDGE_COMMIT',
            "chart_values": {
                "create_mock_upstream": true,
                "mock_image": '$MOCK_GATEWAY_COMMIT',
                "mock_upstream_replicas" : 2,
                "argo": {
                  "execute": true,
                  "tf_commit": '$TERRAFORM_COMMIT',
                  "services": "request-termination"
                },
                "ephemeral_cache":true,
                "ephemeral_db":true,
                "database":{
                  "bootstrap":true
                },
                "edge_cpu_requests" : "200m",
                "edge_replicas" : 2
            }
        },
        {
            "name": "authz",
            "commit_id": "'"$AUTHZ_COMMIT"'",
            "chart_values": {
                "ephemeral_db": true,
                "policies": [
                    {
                      "file": "e2e/2022_05_04_e2e_authenticated",
                      "orgid": "razorpay",
                      "user": "spine",
                      "ingest": true
                    },
                    {
                      "file": "e2e/2022_11_10_e2e_authenticated",
                      "orgid": "razorpay",
                      "user": "spine",
                      "ingest": true
                    }
                ],
                 "authz_admin_replicas":2,
                 "authz_enforcer_replicas":2
            }
        }
    ]
}'

echo $DATA

# Makes request.
RESPONSE=$(
    curl -X POST -s -i \
        -H "Accept: application/json" \
        -H "Authorization: Bearer $ARGO_TOKEN" \
        -H "Content-Type: application/json" \
        -d "$DATA" \
        $ARGO_WEBHOOK_URL
)

echo "$RESPONSE"

# Returns 0 if response code is 200.
echo "$RESPONSE" | grep -q "^HTTP/1.1 200"
