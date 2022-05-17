#!/bin/bash

apk add jq
apk add curl

log_file_name="test-output.log"

Metrics="$(cat $log_file_name | grep Tests: | tr -d .)"
metrics_file_name+="test-metrics.dat"
IFS=','
for i in $Metrics; do
  echo "$i" >> $metrics_file_name
done

START_TIME=$( cut -d "," -f 1 /${GITHUB_WORKSPACE}/utMetrics.csv)
END_TIME=$( cut -d "," -f 2 /${GITHUB_WORKSPACE}/utMetrics.csv)
declare -i TOTAL=$(cat $metrics_file_name | grep Tests: | awk '{s+=$2} END {print s}')
declare -i SKIPPED=$(cat $metrics_file_name | grep Skipped: | awk '{s+=$2} END {print s}')
declare -i ERROR=$(cat $metrics_file_name | grep Errors: | awk '{s+=$2} END {print s}')
declare -i FAILURE=$(cat $metrics_file_name | grep Failures: | awk '{s+=$2} END {print s}')

FAILED=$(expr $ERROR + $FAILURE)
PASSED=$(expr $TOTAL - $SKIPPED)
PASSED=$(expr $PASSED - $FAILED)

echo "Start Time - ${START_TIME}"
echo "End Time - ${END_TIME}"
echo "Total - ${TOTAL}"
echo "Skipped - ${SKIPPED}"
echo "Failed - ${FAILED}"
echo  "Passed - ${PASSED}"

CURL_URL='https://sonar.razorpay.com/api/measures/component_tree?metricKeys=coverage&component=AuthServiceDevCoverageCheck'
sleep 5
curl --location --request GET $CURL_URL -u $SONARQUBE_TOKEN:"" > sonar.json
jq -r '.baseComponent.measures[0].value' sonar.json

code_coverage=$( jq -r '.baseComponent.measures[0].value' sonar.json | cut -d "." -f 1)
echo $code_coverage

echo "INSERT INTO UTCoverage VALUES (\"AUTH_SERVICE\",\"${GIT_COMMIT}\",\"${BRANCH}\",\"Payments\",\"{\\\"tags\\\": [\\\"Apps\\\"]}\",\"${code_coverage}\",\"${START_TIME}\",\"${END_TIME}\",\"${PASSED}\",\"${FAILED}\",\"${SKIPPED}\");"
curl -X POST \
       https://mock-go.qa.razorpay.in/insert_qa_iteration \
       -H "content-type: text/plain" \
       -d "INSERT INTO UTCoverage VALUES (\"AUTH_SERVICE\",\"${GIT_COMMIT}\",\"${BRANCH}\",\"Payments\",\"{\\\"tags\\\": [\\\"Apps\\\"]}\",\"${code_coverage}\",\"${START_TIME}\",\"${END_TIME}\",\"${PASSED}\",\"${FAILED}\",\"${SKIPPED}\");"

if [ -z "$code_coverage" ] || [ $code_coverage = "null" ]; then echo "Value not found"; exit 1; fi
threshold=52
if [ $code_coverage -lt $threshold ]; then echo "failed - threshold unit code coverage check"; exit 1; else echo "success"; exit 0; fi
