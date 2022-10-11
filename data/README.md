# Create OAuth Applications in Bulk

To create OAuth Application in bulk, add a CSV file in this folder that has the list of merchants (1 row should have 1 MID).

File naming convention: `yyyy_mm_dd_{anytext}.csv`

Once the file is added, trigger the Spinnaker pipeline and provide the filename and other parameters required for creating the applications

For more information, see [BulkApplicationCreate.php](../app/Console/Commands/BulkApplicationCreate.php)

# Pipelines
 - [Stage](https://deploy.razorpay.com/#/applications/stage-auth/executions/configure/13aefcd4-375d-4ccb-b043-85aa142d3760)
 - [Automation](https://deploy.razorpay.com/#/applications/automation-auth/executions/configure/d85a6ced-d665-4feb-ab28-27cd7749b45f)
 - [Production](https://deploy.razorpay.com/#/applications/prod-auth/executions/configure/b0d451f9-49be-49d9-ada9-75ef7e3cc790)

