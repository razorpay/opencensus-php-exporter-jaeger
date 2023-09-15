## Contributing to Auth Service
Auth service is Razorpay’s OAuth service and powers auth.razorpay.com. It uses [razorpay/oauth](https://github.com/razorpay/oauth) library and depends on it for many core functionalities. Same library is used by API Monolith.

Currently, auth service does not use the master branch of OAuth library. Instead, it uses a branch called oauth_dualwrite ([ref](https://github.com/razorpay/auth-service/blob/aea72be27451a127a4ddd9845a11718b084ec93d/composer.json#L19)). This is a tech debt on our part and eventually there will be only one branch i.e. master. 

## Making Code Changes
To contribute to Auth service, it is possible that you will also need to make changes in OAuth library. Here’s a sequence of steps you can follow:
1. Clone auth service repo and create a branch from master.
2. Run `composer update` to install all dependencies. OAuth library code will now be available in `vendor/razorpay/oauth` directory.
3. Make code changes in auth service repo if you require. 
4. If you need to make code changes in the OAuth library, you can follow the following steps:
   * Clone `razorpay/oauth` and create a branch from `oauth_dualwrite`, make all the required changes and push them to remote.
   * In auth service, specify your OAuth branch’s name [here](https://github.com/razorpay/auth-service/blob/aea72be27451a127a4ddd9845a11718b084ec93d/composer.json#L19) (`dev-<branch_name>`). Then run `composer update` which will pull your OAuth library changes from remote. This will update the `composer.json` and `composer.lock` files in your auth service repo.
6. For testing, you can now either set up auth service in local by following the [README](https://github.com/razorpay/auth-service#auth-microservice) or deploy the auth service commit in devstack.
7. If you want to test token generation, you’d also need to bring up Edge on devstack. Otherwise, the token creation requests would fail.
8. If you need to add secrets, read [this](https://github.com/razorpay/auth-service#secret-management) note
9. Once manual testing is done and UTs and FTs are added, raise two PRs - one for auth service and one for OAuth library (if applicable).
10. Once PRs are approved, do another round of testing if required. 
11. If OAuth changes were involved,
    * Merge the OAuth PR to `oauth_dualwrite` branch. 
    * In auth service, revert the change made in 4.b and run `composer update`. Push the changes to remote.
14. Raise another PR to merge the changes to the OAuth master branch by cherry picking the commit merged to `oauth_dualwrite`. Merge the PR after approval.
15. Merge Auth service PR 🎉

## Deployment

1. It is the responsibility of the change owner to deploy and monitor the changes. This should be taken up right after merging the changes to master. Make sure all CI checks pass before going ahead.
2. Ensure that the secrets are updated in the correct location before deploying, if any.
3. Execute [deployment-pipeline-v2](https://deploy.razorpay.com/#/applications/prod-auth/executions?q=dep&pipeline=deployment-pipeline-v2), after informing to @spine-edge-oncall. This firsts deploy changes to [canary](https://deploy.razorpay.com/#/applications/canary-auth/executions?pipeline=canary-deployment-v2) and waits for manual judgment before moving to prod.
4. To monitor the changes in canary, refer to the canary panels in [this](https://vajra.razorpay.com/d/KXKw41nMk/auth-service?orgId=1) Vajra dashboard.
5. Traffic on auth service is generally low so request failures may not be visible in the Vajra charts. Use coralogix to inspect for failures by selecting the application as `auth` and subsystem as `auth-canary`.
6. Once canary observations are complete and deployment is good to move to prod, [approve](https://deploy.razorpay.com/#/applications/prod-auth/executions/01HA97V5E4D9E15XKXP1TAQ4TE?q=dep&pipeline=deployment-pipeline-v2&stage=4&step=0&details=manualJudgment) the production deployment stage.

## Hotfix

1. To deploy the hotfix execute the [deployment-pipeline-v2](https://deploy.razorpay.com/#/applications/prod-auth/executions?q=dep&pipeline=deployment-pipeline-v2) with `deployment_type=hotfix`. this deploys the commit provided on canary and prod in parallel.



## [TODO] How to Test Changes
1. How to test authorize flow and relevant code files.
2. Add details about testing UI changes
3. Testing on devstack base setup
4. Add some test credentials


