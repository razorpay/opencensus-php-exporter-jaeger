# Create OAuth Applications in Bulk

1. add a CSV file in `/data` folder that has the list of merchants (1 row should have 1 MID).refer `/data/sample.csv` file. File naming convention: `yyyy_mm_dd_{anytext}.csv`.

2. push the changes to github and wait for the build to complete on github actions.

3. once done trigger the Spinnaker [pipeline](https://deploy.razorpay.com/#/applications/prod-auth/executionspipeline=Create%20OAuth%20Applications%20in%20Bulk) and provide required parameters as shown in below example. use commit id from step 2.

    |example||
    |---------------------------|-------------------------------------------|
    | application_name          | RX Mobile                                 |
    | application_type          | mobile_app                                |
    | application_website       | https//www.razorpay.com/x                 |
    | commit_id                 | b545781286405857f956340b81649705dfb920ac  |
    | file_name                 | 2022_11_10_rx_mobile_11_adhoccsv"         |
    | line_start_from           | 0                                         |
    | use_separate_outbox_job   | true                                      |

4. Once pipeline is started check,
    1. logs of [artisan job](https://service.in.sumologic.com/ui/#/search/create?id=6T8ZigpeoSW0gmWHvA6Am2JInLtbJlgGpzEk1Ahh) to monitor failures in sync jobs.
    2. [auth-outbox-relay](https://service.in.sumologic.com/ui/#/search/create?id=tk9eBIFCiukUA10cvczUtAxO8JClukXC796Rwcsh) logs to check if `create_client_bulk` jobs are failing or not.
    3. to make sure number of jobs spawned by artisan job and number of outbox jobs successfully processed by outbox-relay, compare the count using [outbox-logs](https://service.in.sumologic.com/ui/#/search/create?id=MhCZIlvr6ST9ckYlb5pzc6aut8xFpLaKaCx5XVSu), [artisan-jobs-logs](https://service.in.sumologic.com/ui/#/search/create?id=pbBUNUHN7OXc2HeqWOWbLZfu97fZmeZUQlIb6bSV).
    4. step 3 will help in making sure that non of the outbox job failed.

5. after completion of `BulkApplicationCreate` same pipeline will execute [BulkApplicationValidate](./BulkApplicationValidate.php) job after few mins. which will check for client consistency between auth service and EDGE for all mids mentioned in csv file. check the [logs](https://service.in.sumologic.com/ui/#/search/create?id=UPyBE4pInGhF09qM08YjFLIWu39ISy1LEAvGUtIR) of `BulkApplicationValidate` job to check if there are any mids whose clientids are not synced to edge. if found any mids in logs rerun the pipeline with updated csv file with mids from step 5.


## Pipelines

- [Stage](https://deploy.razorpay.com/#/applications/stage-auth/executions/configure/13aefcd4-375d-4ccb-b043-85aa142d3760)
- [Automation](https://deploy.razorpay.com/#/applications/automation-auth/executions/configure/d85a6ced-d665-4feb-ab28-27cd7749b45f)
- [Production](https://deploy.razorpay.com/#/applications/prod-auth/executions/configure/b0d451f9-49be-49d9-ada9-75ef7e3cc790)
