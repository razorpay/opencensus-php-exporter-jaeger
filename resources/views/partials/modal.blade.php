<style>
    .modal-wrapper {
        position: fixed;
        z-index: 1112;
        display: none;
    }

    .modal-backdrop {

        position: fixed;
        overflow: auto;
        inset: 0px;

        transition-duration: 300ms;
        transition-timing-function: cubic-bezier(0, 0, 0, 1);
        transition-property: opacity;
        opacity: 1;
        background-color: rgba(39, 45, 53, 0.64);
    }

    @keyframes modal-transition {
        0% {
            opacity: 0;
            transform: translate(-50%, -50%) scale(0.9) translateY(20px);
        }

        100% {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1) translateY(0px);
        }
    }

    .modal {
        display: flex;
        flex-direction: column;
        max-height: 80vh;
        width: calc(100vw - 48px);
        min-width: 320px;
        max-width: 400px;
        top: 50%;
        left: 50%;
        background-color: rgb(255, 255, 255);
        border-radius: 16px;
        box-shadow: rgba(19, 38, 68, 0.18) 0px 24px 48px -12px;
        position: fixed;
        transform: translate(-50%, -50%);
        opacity: 1;
        animation: 300ms cubic-bezier(0, 0, 0, 1) 0s 1 normal none running modal-transition;

        -webkit-text-size-adjust: 100%;
        -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
        --base-color: #fff;
        font-family: "Lato", "Lato-Regular", "Helvetica Neue", Helvetica, Arial, sans-serif;
        font-size: 14px;
        line-height: 1.42857143;
        color: #58666e;
        -webkit-font-smoothing: antialiased;
        box-sizing: border-box;
    }

    .modal-header-wrapper {
        display: flex;
        flex-direction: row;
        overflow: hidden auto;
        padding: 20px 20px 12px;
        height: auto;
    }

    .modal-header {
        flex: 1 1 auto;
        padding-right: 16px;

        color: rgb(19, 38, 68);
        font-family: Lato, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
        font-size: 1rem;
        font-weight: 700;
        font-style: normal;
        text-decoration-line: none;
        line-height: 1.5rem;
        margin: 0px;
        padding: 0px;
        display: inline-block;
    }

    .modal-close::after {
        display: inline-block;
        height: 28px;
        content: "\00d7";
        font-size: 28px;
        cursor: pointer;
    }

    .modal-body {
        overflow: hidden auto;
        padding: 20px;
        font-size: 16px;
        line-height: 24px;
        color: rgba(33, 53, 84, 0.67);
    }

    .modal-footer {
        border-top: 1px solid rgba(93, 109, 134, 0.08);
        height: 92px;
        padding: 20px 20px 0px;
    }

    .modal-body-heading {
        color: #213554;
        font-weight: 700;
        font-size: 20px;
        line-height: 28px;
        padding-bottom: 36px;
    }

    .modal-pricing-plans .modal-body {
        padding: 36px;
    }

    .modal-pricing-plans .btn.primary {
        width: 210px;
    }
</style>
<div class="modal-wrapper" id="modal-wrapper">
    <div class="modal-backdrop"></div>
    <div class="modal">
        <div class="modal-header-wrapper">
            <div class="modal-header" id="modal-header"></div>
            <div class="modal-close" id="modal-close"></div>
        </div>
        <div class="modal-body" id="modal-body"></div>
        <div class="modal-footer" id="modal-footer"></div>
    </div>
</div>


<script>
    window.openModal = function(args) {
        if (args.customClass) {
            $('#modal-wrapper').addClass(args.customClass);
        }
        $('#modal-header').html(args.header);
        $('#modal-body').html(args.body);
        $('#modal-footer').html(args.footer);
        $('#modal-wrapper').show();
    }
    window.closeModal = function() {
        $('#modal-wrapper').hide();
    };
    (function() {
        $('#modal-close').click(function() {
            closeModal();
        })
    })();
</script>