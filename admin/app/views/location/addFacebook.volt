<!-- BEGIN SAMPLE FORM PORTLET-->
<style type="text/css">
    .pnlAddLocation.short {
        min-height: 230px !important;
    }

    .backBtn {
        float: right;
        padding-left: 35px;
        padding-right: 35px;
        line-height: 17px;
    }

    .editBtn {
        display: inline-block;
        margin-left: 8px;
        margin-top: 11px;
    }

    .connect-btn {
        background-color: #c01209;
        border: medium none;
        border-radius: 26px !important;
        color: #ffffff;
        font-weight: bold;
        height: 77px;
        /*width: 483px;*/
        font-size: 30px;
        background-color: #449d44;
        border-color: #398439;
    }

    ol {
        font-size: 20px;
        padding-left: 50px;
        margin-top: 30px;
        margin-bottom: 30px;
    }

    li {
        padding-top: 10px;
        padding-bottom: 10px;
    }

    @media screen and (max-width: 991px) {
        .img-block {
            display: none;
        }

        #register-submit-btn {
            margin-top: 10px;
        }

        .connect-btn {
            height: 50px;
            font-size: 22px;
        }
    }
</style>
<div class="portlet light bordered">
    {% if !ComingFromSignup %}
        <div class="portlet-title">
            <div class="">
                <a href="/location" class="btnLink backBtn" style="">Back</a>
                <i class="icon-pencil fa-user"></i>
                <span class="caption-subject bold uppercase editBtn">Edit</span>
            </div>
        </div>
    {% endif %}
    <div class="portlet-body form">
        {{ content() }}
                        

            <!-- FACEBOOK BLOCK -->
            
            <div class="row">
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-12">
                            <h1 style="font-size: 27px;">Connect your Facebook Account to monitor all reviews from Facebook</h1>
                            <ol>
                            <li>Click "CONNECT FACEBOOK PAGE" button below</li>
                            <li>Login into your account where you manage your business on Facebook</li>
                            <li>Authorize our application to access your account on Facebook</li>
                            <li>Choose your business listing</li>
                            </ol>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="button" class="connect-btn btn-lg btn-block center-block uppercase" onclick="window.location.href = '/location/getAccessToken/{{ location_id }}/{{ ComingFromSignup }}';">CONNECT FACEBOOK PAGE</button>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="row img-block">
                        <div class="col-md-12">
                            <img class="img-responsive center-block" style="margin-top: 20px; margin-bottom: 20px;" src="/img/Facebook.png">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="button" onclick="window.location.href = '/location/addYelp/{{ location_id }}';" id="register-submit-btn" class="btnsignup uppercase center-block" style="width: 80%">SKIP: I DON'T HAVE AN ACCOUNT</button>
                        </div>
                    </div>
                </div>
            </div>

    </div>
    <input type="hidden" id="facebook_access_token" value="<?=$facebook_access_token?>"/>

   
</div>

<div style="clear: both;">&nbsp;</div>
<input type="hidden" name="form" value="2"/>

<!-- END FORM -->

<div style="clear: both;">&nbsp;</div>
<input type="hidden" name="GoToNextStep" value="0" id="GoToNextStep"/>
</form>
</div>
</div>

<script src="/js/location.js" type="text/javascript"></script>
<script src="/js/browser-deeplink.js" type="text/javascript"></script>

<script type="text/javascript">

    jQuery(document).ready(function ($) {

        // Scanning form validation and ajax submit
        $('#register-submit-btn').on('click', function (e) {
            e.preventDefault();
            $.ajax({
                type: 'POST',
                beforeSend: function () {
                    $("#loader").show();
                    $("#relevant-result-list").hide();
                    $("#scan-tool-landing-page").hide();
                    $(".footerscan").hide();
                    $("#hiddenForm").hide();
                },
                complete: function () {
                    $("#loader").hide();
                    $("#relevant-result-list").show();
                    $(".footerscan").show();
                    // $("#relevant-result-list").empty();
                },
                url: '/session/googlesearchapi/',
                data: {
                    location_name: $.trim($('#location_name').val()),
                    zip: $.trim($('#zip_code').val())
                },
                dataType: 'json',
                //async  : false,
                success: function (response) {
                    if (response.errorMsg) {
                        $("#relevant-result-list").html(response.errorMsg);
                    }
                    else {
                        $("#relevant-result-list").html(response.HTML);
                    }
                }
            });
        });

        $('#urllink, #urllinkyelp').on('click', function (e) {
            e.preventDefault();
            $('#page-wrapper').show();
            $('.overlay').show();
        });
        $('.overlay, .closelink').on('click', function (e) {
            e.preventDefault();
            $('#page-wrapper').hide();
            $('.overlay').hide();
        });

    });


</script>