<!-- This will contain all styles for card component -->
<style>
  p {
    margin: 0;
  }

  .card {
      z-index: 2;
      width: 100vw;
      display: flex;
      flex-direction: column;
    }

  @media (max-width: 768px) {
    .card {
      height: 100vh;
      background: #040847;
      background-image: url("https://easy.razorpay.com/federated-bundles/onboarding/build/browser/static/static/logos/vector.svg");
      background-position: inherit;
      background-size: cover;
    }
  }

  @media (min-width: 768px) {
    .card {
      background: #FFFFFF;
      position: relative;
      justify-content: space-between;
      align-items: center;
      width: 650px;
      border: 1px solid rgba(78, 90, 109, 0.08);
      /* Elevation/16 */

      box-shadow: 0px 10px 18px rgba(21, 45, 75, 0.1), 0px 0px 1px rgba(21, 45, 75, 0.2);
      border-radius: 16px;
    }
  }

  .card-header {
    display: flex;
    justify-content: center;
    flex-direction: column;
    text-align: center;
    align-items: center;
    width: 100vw;
    margin-bottom: 24px;
    margin-top: 64px;
  }

  @media (min-width: 768px) {
    .card-header {
      align-items: initial;
      text-align: initial;
      height: 196px;
      width: 95%;
      background: #040847;
      border-radius: 12px;
      margin-top: 18px;
      margin-bottom: 0;
    }
  }


  .access-heading {
    font-size: 24px;
    font-weight: 700;
    color: #ffffff;
    line-height: 32px;
    margin: 0;
  }

  @media (max-width: 768px) {
    .access-heading {
      text-align: center;
      font-size: 20px;
      font-weight: 700;
      color: #ffffff;
      line-height: 28px;
    }
  }

  .heading-wrapper {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    margin-right: 48px;
  }

  @media (max-width: 768px) {
    .heading-wrapper {
      display: flex;
      flex-direction: column-reverse;
      margin-right: 0px;
      gap: 15px;
    }
  }

  .image-container {
    display: flex;
    height: 34px;
    justify-content: center;
  }

  .image-container > img {
    object-fit: contain;
  }

  @media (min-width: 768px) {
    .image-container {
      width: 80px;
    }
  }

  .heading-with-image {
      margin: 0px 24px;
    }

  @media (min-width: 768px) {
    .heading-with-image {
        width: 315px;
        margin-left: 48px;
    }
  }

  .card-footer {
    @if ($data['showPartnerPricingAgreement'] === true)
    margin-top: 10px;
    @else
    margin-top: 63px;
    @endif
    height: 128px;
    width: 100%;
    border: 1px solid rgba(93, 109, 134, 0.08);
    border-radius: 0px 0px 16px 16px;
    display: flex;
    align-items: center;
  }

  @media (max-width: 768px) {
    .card-footer {
      border: none;
      background: #FFFFFF;
    }
  }

  .divider {
    width: 516px;
    height: 0px;
    margin-top: 24px;
    margin-left: 48px;
    /* Surface/Border/Normal/lowContrast */

    border: 2px solid rgba(121, 135, 156, 0.18);
  }

  @media (max-width: 768px) {
    .divider {
      display: none;
    }
  }

  .logged-user {
    font-size: 14px;
    margin-top: 8px;
    margin-left: 48px;
    color: #79879C;
  }

  @media (max-width: 768px) {
    .logged-user {
      margin-left: 0px;
    }
  }


  .scope {
    margin-left: 74px;
    color: #5D6D86;
  }

  @media (max-width: 768px) {
    .scope {
      margin-top: 24px;
      margin-left: 24px;
    }
  }

  .scope-list {
    font-size: 18px;
    line-height: 24px;
    margin-top: 16px;
    margin-left: 20px;
  }

  @media (max-width: 768px) {
    .scope-list {
      font-size: 14px;
      line-height: 20px;
    }
  }

  .scope-heading {
    color: #213554;
    font-weight: 700;
    font-size: 20px;
    line-height: 28px;
  }

  @media (max-width: 768px) {
    .scope-heading {
      font-size: 14px;
      line-height: 24px;
    }
  }

  .policies {
    @if ($data['showPartnerPricingAgreement'] === true)
    margin: 20px 104px 0px 74px;
    @else
    margin: 40px 104px 0px 74px;
    @endif
  }

  @media (max-width: 768px) {
    .policies {
      margin: 32px 24px 0px 24px;
    }
  }

  .policies-text {
    font-size: 16px;
    line-height: 24px;
    color: rgba(33, 53, 84, 0.67);
  }

  @media (max-width: 768px) {
    .policies-text {
      font-size: 14px;
      line-height: 20px;
    }
  }

  button {
    color: black;
  }

  .primary {
    font-weight: 700;
    font-size: 16px;
    color: #ffffff;
    padding: 0px;
    width: 236px;
    height: 56px;

    transition: background-color 250ms cubic-bezier(0.4, 0, 0.2, 1) 0ms, box-shadow 250ms cubic-bezier(0.4, 0, 0.2, 1) 0ms, border-color 250ms cubic-bezier(0.4, 0, 0.2, 1) 0ms, color 250ms cubic-bezier(0.4, 0, 0.2, 1) 0ms;
    background: #1566F1;
    box-shadow: 0px 8px 16px 4px rgba(21, 102, 241, 0.2);
    border-radius: 12px;
  }

  .primary:active {
    box-shadow: rgba(0, 0, 0, 0.2) 0px 5px 5px -3px, rgba(0, 0, 0, 0.14) 0px 8px 10px 1px, rgba(0, 0, 0, 0.12) 0px 3px 14px 2px
  }

  .tertiary {
    height: 56px;
    font-weight: 700;
    font-size: 16px;
    line-height: 24px;
    color: rgb(21, 102, 241);
    background: transparent;
  }

  .tertiary:disabled {
    pointer-events: none;
    cursor: default;
    color: lightgrey;
  }

  @media (max-width: 768px) {
    .cancel-form {
      width: 40%
    }

    .cancel-form > button {
      width: 100%
    }

    .authorize-form  {
      width: 60%;
    }

    .authorize-form > button {
      width: 100%;
    }
  }


  .tertiary:hover {
    background: rgba(21, 102, 241, 0.04);
  }

  .primary:hover {
    text-decoration: none;
    background-color: rgb(0, 51, 229);
    box-shadow: rgba(0, 0, 0, 0.2) 0px 2px 4px -1px, rgba(0, 0, 0, 0.14) 0px 4px 5px 0px, rgba(0, 0, 0, 0.12) 0px 1px 10px 0px;
  }

  .primary:disabled {
    cursor: default;
    color: rgb(196, 203, 215);
    box-shadow: none;
    background-color: rgba(50, 70, 100, 0.12);
  }

  .button-wrapper {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    width: 100%;
    margin-right: 36px;
    gap: 10px;
  }

  @media (max-width: 768px) {
    .button-wrapper {
      justify-content: center;
      margin-right: 0px;
      padding: 0px 20px;
    }
  }

  .card-body {
    @if ($data['showPartnerPricingAgreement'] === true)
    margin-top: 20px;
    @else
    margin-top: 53px;
    @endif
  }

  @media (max-width: 768px) {
    .card-body {
      background: #FFFFFF;
      border-radius: 16px;
      margin: 0px 20px 64px 20px;
    }
  }

  .underline {
    text-decoration: none;
    cursor: pointer;
    color: #1566F1;
    border-bottom: 1px solid currentColor;
  }

  .rzp-logo {
      display: flex;
      align-items: center;
      justify-content: center;
      position: fixed;
      width: 100vw;
      bottom: 20px;
  }

  img[src*="images/rzp-logo-light.svg"] {
    width: 77px;
    height: 16px;
  }

  @media (min-width: 768px) {
    .rzp-logo {
      display: none;
    }
  }
</style>

<div class="card">
  <div class="card-header">
    <div class="heading-wrapper">
      <div class="heading-with-image">
        <p class="access-heading">{{$data['application']['name']}} wants access to your Razorpay Account</p>
      </div>
      <div class="image-container">
        <img class="application-logo"/>
      </div>
    </div>
    <div class="divider"></div>
    <div class="logged-user">
      <span>Logged in as <strong id="user_email"></strong> </span>
    </div>
  </div>
  <div class="card-body">
  <div class="error-container"></div>
    <section class="scope">
      <p class="scope-heading">This will allow {{$data['application']['name']}} to: </p>
      <ul class="scope-list">
        @if($data['scope_descriptions'])
            @foreach($data['scope_descriptions'] as $item)
                <li>{{$item}}</li>
            @endforeach
        @endif
      </ul>
    </section>
    <section class="policies">
    <p class="policies-text">You may review detailed
      @foreach($data['scope_policies'] as $text => $link)
        @if ($loop->first and $loop->last)
            <a class="underline" href={{$link}} target="_blank">{{$text}}</a>.
        @elseif ($loop->first)
            <a class="underline" href={{$link}} target="_blank">{{$text}}</a>
        @elseif ($loop->last)
            and <a class="underline" href={{$link}} target="_blank">{{$text}}</a>.
        @else
            , <a class="underline" href={{$link}} target="_blank">{{$text}}</a>
        @endif
    @endforeach
    You can remove this app from your account under Settings.</p>
    </section>
    @if (empty($data['custom_policy_url']) === false)
        @if (isset($data['is_platform_fee_enabled']) === true and $data['is_platform_fee_enabled'] === true)
            <section class="policies">
              <p class="policies-text">
                  You are also authorizing Razorpay to deduct merchant services fee for each transaction as per terms specified
                  for {{$data['application']['name']}} <a class="underline" href={{$data['custom_policy_url']}} target="_blank">here</a>.
              </p>
            </section>
        @else
            <section class="policies">
                <p class="policies-text">
                    You are also authorizing {{$data['application']['name']}} to deduct partner services fee as per terms specified
                    <a class="underline" href={{$data['custom_policy_url']}} target="_blank">here</a>.
                </p>
            </section>
        @endif
    @endif
    @if ($data['showPartnerPricingAgreement'] === true)
    <section class="policies">
      <p class="policies-text">
        You agree to pay the <span class="underline partner-pricing-plans-link">fees specified</span> for
        all transactions initiated through the Partner {{$data['application']['name']}}.
        For transactions initiated otherwise, fees as agreed with Razorpay separately shall apply.
      </p>
    </section>
    @endif
    <div class="card-footer">
    <div class="button-wrapper">
      <form class="cancel-form" method="POST" action="/authorize">
        {{ method_field('DELETE') }}
        <input type="hidden" name="token" class="verify_token" value=""/>
        <input
            type="hidden"
            name="merchant_id"
            class="merchant-id"
            value=""
        />
        <button class="btn tertiary btn-default" disabled onclick="cancelBtnClick()"> Cancel </button>
      </form>
      <form class="authorize-form" method="POST" action="/authorize">
          <input type="hidden" name="token" class="verify_token" value=""/>
          <input
              type="hidden"
              name="merchant_id"
              class="merchant-id"
              value=""
          />
          <input
              type="hidden"
              name="dashboard_access"
              class="dashboard-access"
              value="false"
          />
          @if (empty($data['custom_policy_url']) === false)
              <input
                  type="hidden"
                  name="custom_policy_url"
                  class="custom-policy-url"
                  value={{$data['custom_policy_url']}}
              />
          @endif
        <button class="btn primary btn-submit" disabled onclick="authorizeBtnClick()"> Authorize </button>
      </form>
    </div>
  </div>
  </div>
  <div class="rzp-logo">
    <p style="color: #FFFFFF;">Powered by</p> <img src="https://easy.razorpay.com/federated-bundles/onboarding/build/browser/static/src/App/Onboarding/images/rzp-logo-light.svg"/>
  </div>
</div>


<script type="text/javascript">
  (function() {
    $('.partner-pricing-plans-link').click(function() {
      window.onProceedClick = function(){
        window.open('{{$data["partner_pricing_plans_url"] ?? "https://dashboard.razorpay.com/app/partner-pricing-plans"}}', '_blank');
        window.closeModal()
      }
      window.openModal({
        title: "",
        body: '\
        <div class="modal-body-heading">You are being taken to the Pricing&nbsp;Plans&nbsp;Page</div>\
        <div>This will open a new tab. Please come back to this page to Agree to the authorisation, as payments cannot be collected without it.</div><br/>\
      ',
        footer: '\
        <div class="button-wrapper">\
          <button class="btn tertiary btn-default" onclick="window.closeModal()"> Cancel </button>\
          <button class="btn primary btn-submit" onclick="window.onProceedClick()"> Proceed </button>\
        </div>\
        ',
        customClass: 'modal-pricing-plans'
      });
    })
  })();
</script>
