# install dependencies
apk update && apk add jq && rm -rf /var/cache/apk/*

# defining globals
MASTER_PROJECT="AuthService"
DEV_PROJECT="AuthServiceDevCoverageCheck"
IS_TEST_COVERAGE_OK="true"

# defining the thresholds
CODE_COVERAGE_THRESHOLD="89.0"
echo ::set-output name=code_coverage_threshold::"$CODE_COVERAGE_THRESHOLD"

# adding sleep to avoid pulling sonar coverage of older commits.
sleep 10

# API Endpoints
MEASURES_COMPONENT_TREE_API=$SONAR_HOST'/api/measures/component_tree'
PROJECT_ANALYSES_SEARCH_API=$SONAR_HOST'/api/project_analyses/search'
MEASURES_SEARCH_HISTORY_API=$SONAR_HOST'/api/measures/search_history'

# get master metrics - code coverage
CURL_URL=$MEASURES_COMPONENT_TREE_API'?metricKeys=coverage&component='$MASTER_PROJECT
curl --location --request GET "$CURL_URL" -u $SONAR_TOKEN:"" > sonar_master.json
MASTER_CODE_COVERAGE=$(jq '.baseComponent.measures | .[].value' sonar_master.json)
echo ::set-output name=master_code_coverage::"$MASTER_CODE_COVERAGE"

# get master metrics - duplicate lines desnity
CURL_URL=$MEASURES_COMPONENT_TREE_API'?metricKeys=duplicated_lines_density&component='$MASTER_PROJECT
curl --location --request GET "$CURL_URL" -u $SONAR_TOKEN:"" > sonar_master.json
MASTER_DUPLICATE_LINES_DENSITY=$(jq '.baseComponent.measures | .[].value' sonar_master.json)
echo ::set-output name=master_duplicated_lines_density::"$MASTER_DUPLICATE_LINES_DENSITY"

# get master metrics - vulnerabilities
CURL_URL=$MEASURES_COMPONENT_TREE_API'?metricKeys=vulnerabilities&component='$MASTER_PROJECT
curl --location --request GET "$CURL_URL" -u $SONAR_TOKEN:"" > sonar_master.json
MASTER_VULNERABILITIES=$(jq '.baseComponent.measures | .[].value' sonar_master.json)
echo ::set-output name=master_vulnerabilities::"$MASTER_VULNERABILITIES"

# get master metrics - bugs
CURL_URL=$MEASURES_COMPONENT_TREE_API'?metricKeys=bugs&component='$MASTER_PROJECT
curl --location --request GET "$CURL_URL" -u $SONAR_TOKEN:"" > sonar_master.json
MASTER_BUGS=$(jq '.baseComponent.measures | .[].value' sonar_master.json)
echo ::set-output name=master_bugs::"$MASTER_BUGS"

# get master metrics - code smells
CURL_URL=$MEASURES_COMPONENT_TREE_API'?metricKeys=code_smells&component='$MASTER_PROJECT
curl --location --request GET "$CURL_URL" -u $SONAR_TOKEN:"" > sonar_master.json
MASTER_CODE_SMELLS=$(jq '.baseComponent.measures | .[].value' sonar_master.json)
echo ::set-output name=master_code_smells::"$MASTER_CODE_SMELLS"

# get dev project details
CURL_URL=$PROJECT_ANALYSES_SEARCH_API'?project='$DEV_PROJECT'&ps=10'
curl --location --request GET "$CURL_URL" -u $SONAR_TOKEN:"" > sonar.json
PR_DATE=$(jq '.analyses | first(.[] | select(.projectVersion=="'$GIT_SHA'")).date' sonar.json)

# check if PR date is the first item in the index
FIRST_DATE=$(jq '.analyses | .[0] | .date' sonar.json)
if [ "$PR_DATE" == "$FIRST_DATE" ];
then
  echo "This is the latest commit as per sonarqube"

  # get dev metrics - code coverage
  CURL_URL=$MEASURES_COMPONENT_TREE_API'?metricKeys=coverage&component='$DEV_PROJECT
  curl --location --request GET "$CURL_URL" -u $SONAR_TOKEN:"" > sonar.json
  DEV_CODE_COVERAGE=$(jq '.baseComponent.measures | .[].value' sonar.json)
  echo ::set-output name=dev_code_coverage::"$DEV_CODE_COVERAGE"

  # get dev metrics - duplicate lines desnity
  CURL_URL=$MEASURES_COMPONENT_TREE_API'?metricKeys=duplicated_lines_density&component='$DEV_PROJECT
  curl --location --request GET "$CURL_URL" -u $SONAR_TOKEN:"" > sonar.json
  DEV_DUPLICATE_LINES_DENSITY=$(jq '.baseComponent.measures | .[].value' sonar.json)
  echo ::set-output name=dev_duplicated_lines_density::"$DEV_DUPLICATE_LINES_DENSITY"

  # get dev metrics - vulnerabilities
  CURL_URL=$MEASURES_COMPONENT_TREE_API'?metricKeys=vulnerabilities&component='$DEV_PROJECT
  curl --location --request GET "$CURL_URL" -u $SONAR_TOKEN:"" > sonar.json
  DEV_VULNERABILITIES=$(jq '.baseComponent.measures | .[].value' sonar.json)
  echo ::set-output name=dev_vulnerabilities::"$DEV_VULNERABILITIES"

  # get dev metrics - bugs
  CURL_URL=$MEASURES_COMPONENT_TREE_API'?metricKeys=bugs&component='$DEV_PROJECT
  curl --location --request GET "$CURL_URL" -u $SONAR_TOKEN:"" > sonar.json
  DEV_BUGS=$(jq '.baseComponent.measures | .[].value' sonar.json)
  echo ::set-output name=dev_bugs::"$DEV_BUGS"

  # get dev metrics - code smells
  CURL_URL=$MEASURES_COMPONENT_TREE_API'?metricKeys=code_smells&component='$DEV_PROJECT
  curl --location --request GET "$CURL_URL" -u $SONAR_TOKEN:"" > sonar.json
  DEV_CODE_SMELLS=$(jq '.baseComponent.measures | .[].value' sonar.json)
  echo ::set-output name=dev_code_smells::"$DEV_CODE_SMELLS"
else
  echo "This is an older commit as per sonarqube"

  # get dev project PR metrics
  CURL_URL=$MEASURES_SEARCH_HISTORY_API'?component='$DEV_PROJECT'&metrics=duplicated_lines_density,coverage,bugs,code_smells,vulnerabilities&ps=1000&p=1'
  curl --location --request GET "$CURL_URL" -u $SONAR_TOKEN:"" > sonar.json

  # extract code coverage
  DEV_CODE_COVERAGE=$(jq '.measures | .[] | select(.metric=="coverage").history | .[] | select(.date=='"$PR_DATE"').value' sonar.json)
  echo ::set-output name=dev_code_coverage::"$DEV_CODE_COVERAGE"

  # extract duplicate line density
  DEV_DUPLICATE_LINES_DENSITY=$(jq '.measures | .[] | select(.metric=="duplicated_lines_density").history | .[] | select(.date=='"$PR_DATE"').value' sonar.json)
  echo ::set-output name=dev_duplicated_lines_density::"$DEV_DUPLICATE_LINES_DENSITY"

  # extract vulnerabilities
  DEV_VULNERABILITIES=$(jq '.measures | .[] | select(.metric=="vulnerabilities").history | .[] | select(.date=='"$PR_DATE"').value' sonar.json)
  echo ::set-output name=dev_vulnerabilities::"$DEV_VULNERABILITIES"

  # extract bugs
  DEV_BUGS=$(jq '.measures | .[] | select(.metric=="bugs").history | .[] | select(.date=='"$PR_DATE"').value' sonar.json)
  echo ::set-output name=dev_bugs::"$DEV_BUGS"

  # extract code smells
  DEV_CODE_SMELLS=$(jq '.measures | .[] | select(.metric=="code_smells").history | .[] | select(.date=='"$PR_DATE"').value' sonar.json)
  echo ::set-output name=dev_code_smells::"$DEV_CODE_SMELLS"
fi

# create a file and add headers
touch report.md
echo "|Metric|Master|PR|Comment|" >> report.md
echo "|:---|:---|:---|:---|" >> report.md

# convert the strings into bc format
CODE_COVERAGE_THRESHOLD=$(echo "$CODE_COVERAGE_THRESHOLD" | bc)
DEV_CODE_COVERAGE=$(echo "$DEV_CODE_COVERAGE" | bc)
DEV_DUPLICATE_LINES_DENSITY=$(echo "$DEV_DUPLICATE_LINES_DENSITY" | bc)
DEV_VULNERABILITIES=$(echo "$DEV_VULNERABILITIES" | bc)
DEV_BUGS=$(echo "$DEV_BUGS" | bc)
DEV_CODE_SMELLS=$(echo "$DEV_CODE_SMELLS" | bc)
MASTER_CODE_COVERAGE=$(echo "$MASTER_CODE_COVERAGE" | bc)
MASTER_DUPLICATE_LINES_DENSITY=$(echo "$MASTER_DUPLICATE_LINES_DENSITY" | bc)
MASTER_VULNERABILITIES=$(echo "$MASTER_VULNERABILITIES" | bc)
MASTER_BUGS=$(echo "$MASTER_BUGS" | bc)
MASTER_CODE_SMELLS=$(echo "$MASTER_CODE_SMELLS" | bc)

if [ $(echo "$DEV_CODE_COVERAGE < $CODE_COVERAGE_THRESHOLD" | bc -l) == 1 ];
then
  IS_TEST_COVERAGE_OK="false"
  echo "|Coverage|$MASTER_CODE_COVERAGE|$DEV_CODE_COVERAGE|🚫 Alert! You can improve the coverage by adding test cases.|" >> report.md

# if code coverage is less than master
elif [ $(echo "$DEV_CODE_COVERAGE < $MASTER_CODE_COVERAGE" | bc -l) == 1 ];
then
  IS_TEST_COVERAGE_OK="false"
  echo "|Coverage|$MASTER_CODE_COVERAGE|$DEV_CODE_COVERAGE|⚠️ Don't worry! You can improve the coverage by adding test cases.|" >> report.md

# if code coverage is equal to master
elif [ $(echo "$DEV_CODE_COVERAGE == $MASTER_CODE_COVERAGE" | bc -l) == 1 ];
then
  echo "|Coverage|$MASTER_CODE_COVERAGE|$DEV_CODE_COVERAGE|👍 Cool! You have maintained the code coverage.|" >> report.md

# if code coverage is greater than master
elif [ $(echo "$DEV_CODE_COVERAGE > $MASTER_CODE_COVERAGE" | bc -l) == 1 ];
then
  echo "|Coverage|$MASTER_CODE_COVERAGE|$DEV_CODE_COVERAGE|🎉 Hurray! You improved the code coverage.|" >> report.md
fi

# if duplicate lines density is less than master
if [ $(echo "$DEV_DUPLICATE_LINES_DENSITY < $MASTER_DUPLICATE_LINES_DENSITY" | bc -l) == 1 ];
then
  echo "|Duplicate Lines Density|$MASTER_DUPLICATE_LINES_DENSITY|$DEV_DUPLICATE_LINES_DENSITY|🎉 Kudos! You reduced code duplicates.|" >> report.md

# if duplicate lines density is greater than master
elif [ $(echo "$DEV_DUPLICATE_LINES_DENSITY > $MASTER_DUPLICATE_LINES_DENSITY" | bc -l) == 1 ];
then
  echo "|Duplicate Lines Density|$MASTER_DUPLICATE_LINES_DENSITY|$DEV_DUPLICATE_LINES_DENSITY|⚠️ Avoid duplicates by reusing the code!|" >> report.md

# if duplicate lines density is equal to master
elif [ $(echo "$DEV_DUPLICATE_LINES_DENSITY == $MASTER_DUPLICATE_LINES_DENSITY" | bc -l) == 1 ];
then
  echo "|Duplicate Lines Density|$MASTER_DUPLICATE_LINES_DENSITY|$DEV_DUPLICATE_LINES_DENSITY|👍 Cool! You have not added any duplicate lines of code.|" >> report.md
fi

# if vulnerabilities is less than master
if [ $(echo "$DEV_VULNERABILITIES < $MASTER_VULNERABILITIES" | bc -l) == 1 ];
then
  echo "|Vulnerabilities|$MASTER_VULNERABILITIES|$DEV_VULNERABILITIES|🎉 Nice! Few vulnerabilities got fixed.|" >> report.md

# if vulnerabilities is greater than master
elif [ $(echo "$DEV_VULNERABILITIES > $MASTER_VULNERABILITIES" | bc -l) == 1 ];
then
  echo "|Vulnerabilities|$MASTER_VULNERABILITIES|$DEV_VULNERABILITIES|🚫 Alert! New vulnerabilities found! Please fix them.|" >> report.md

# if vulnerabilities is equal to master
elif [ $(echo "$DEV_VULNERABILITIES == $MASTER_VULNERABILITIES" | bc -l) == 1 ];
then
  echo "|Vulnerabilities|$MASTER_VULNERABILITIES|$DEV_VULNERABILITIES|👍 Cool! You have not added any new vulnerabilities.|" >> report.md
fi

# if bugs is less than master
if [ $(echo "$DEV_BUGS < $MASTER_BUGS" | bc -l) == 1 ];
then
  echo "|Bugs|$MASTER_BUGS|$DEV_BUGS|🎉 Nice! Few bugs got fixed.|" >> report.md

# if bugs is greater than master
elif [ $(echo "$DEV_BUGS > $MASTER_BUGS" | bc -l) == 1 ];
then
  echo "|Bugs|$MASTER_BUGS|$DEV_BUGS|🚫 Alert! New bug found! Please fix them.|" >> report.md

# if bugs is equal to master
elif [ $(echo "$DEV_BUGS == $MASTER_BUGS" | bc -l) == 1 ];
then
  echo "|Bugs|$MASTER_BUGS|$DEV_BUGS|👍 Cool! You have not added any new bugs.|" >> report.md
fi

# if code smells is less than master
if [ $(echo "$DEV_CODE_SMELLS < $MASTER_CODE_SMELLS" | bc -l) == 1 ];
then
  echo "|Code Smells|$MASTER_CODE_SMELLS|$DEV_CODE_SMELLS|🎉 Nice! Few code smells got removed.|" >> report.md

# if code smells is greater than master
elif [ $(echo "$DEV_CODE_SMELLS > $MASTER_CODE_SMELLS" | bc -l) == 1 ];
then
  echo "|Code Smells|$MASTER_CODE_SMELLS|$DEV_CODE_SMELLS| Alert! New code smells found! Please remove them.|" >> report.md

# if code smells is equal to master
elif [ $(echo "$DEV_CODE_SMELLS == $MASTER_CODE_SMELLS" | bc -l) == 1 ];
then
  echo "|Code Smells|$MASTER_CODE_SMELLS|$DEV_CODE_SMELLS|👍 Cool! You have not added any new code smells.|" >> report.md
fi

# export quality report
QUALITY_REPORT=$(cat report.md)
QUALITY_REPORT="${QUALITY_REPORT//'%'/'%25'}"
QUALITY_REPORT="${QUALITY_REPORT//$'\n'/'%0A'}"
QUALITY_REPORT="${QUALITY_REPORT//$'\r'/'%0D'}"
echo ::set-output name=quality_report::"$QUALITY_REPORT"
echo ::set-output name=is_test_coverage_ok::"$IS_TEST_COVERAGE_OK"


# clean up
rm report.md
rm sonar.json
rm sonar_master.json
