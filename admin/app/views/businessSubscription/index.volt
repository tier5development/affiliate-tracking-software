{{ content() }}

<header class="jumbotron subhead" id="reviews">
    <div id="subscription-data" class="hero-unit" data-subscription="{{ subscriptionPlanData | json_encode | escape }}">
        <div class="row">
            {% if showSmsQuota %}
                {{ partial("shared/smsQuota", smsQuotaParams) }}
            {% endif %}
        </div>
        
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="growth-bar transparent pull-right">
                    <a href="/businessSubscription/invoices" disabled class="btn default btn-lg apple-backgound subscription-btn">View Invoices</a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 col-sm-12">
                <div class="growth-bar">
                    PLANS & PAYMENTS
                </div>
            </div>
        </div>

        <div class="row subscription-panel-group plans-and-payments-row">
            <div class="col-md-12 col-lg-8">
                <div class="portlet light bordered dashboard-panel">
                    <div class="portlet-title">
                        <div class="caption">
                            <span>Currently Active Plan</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="panel panel-default apple-backgound">
                            <div class="panel-body">
                                <div id="current-plan" class="responsive-float-left subscription-panel-default-caption">
                                    <div><span id="current-locations" class="bold">{{ subscriptionPlanData['subscriptionPlan']['locations'] }}</span> Location(s)</div>
                                    <div><span id="current-messages" class="bold">{{ subscriptionPlanData['subscriptionPlan']['sms_messages_per_location'] }}</span> Text Messages</div>
                                </div>
                                <div class="responsive-float-right subscription-panel-large-caption">{{ paymentPlan }}</div>
                            </div>
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <div class="quota-display">
                                    <div class="subscription-panel-quota-caption midnight-text">{{ smsQuotaParams['smsSentThisMonth'] }}/{{ subscriptionPlanData['subscriptionPlan']['sms_messages_per_location'] }}</div>
                                    <div class="subscription-panel-quota-caption midnight-text">Messages Sent</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
           
            <div class="col-md-12 col-lg-4">
                <div class="portlet light bordered dashboard-panel payment-info">
                    <div class="portlet-title">
                        <div class="caption">
                            <span class="">Payment Info</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <span class="">No Payment Details</span>
                                </div>
                            </div>
                            <div class="panel-body credit-card-details">
                                <div class="row large">
                                    <div class="col-xs-8 col-md-8">
                                        <div class="form-group">
                                            <input type="text" disabled class="form-control center" name="cardNumber" placeholder="XXXX-XXXX-XXXX-XXXX" autocomplete="cc-exp" required="" aria-required="true">
                                        </div>
                                    </div>
                                    <div class="col-xs-4 col-md-4 pull-right">
                                        <div class="form-group">
                                            <div><img src="/img/cc/{{ registeredCardType }}.png"></div>
                                            <div><span>XX/XX</span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row small">
                                    <div class="col-xs-12 col-md-12">
                                        <div class="form-group">
                                            <input type="text" disabled class="form-control center" name="cardNumber" placeholder="XXXX-XXXX-XXXX-XXXX" autocomplete="cc-exp" required="" aria-required="true">
                                        </div>
                                        <div class="form-group">
                                            <div><img src="/img/cc/{{ registeredCardType }}.png"></div>
                                            <div><span>10/2016</span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-xs-12">
                                        <div class="form-group">
                                            <button type="button" class="btn default btn-lg apple-backgound subscription-btn" data-toggle="modal" data-target="#updateCardModal">Update Card</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 col-sm-12">
                <div class="growth-bar">
                    CHANGE PLAN
                </div>
            </div>
        </div>

        <div class="row subscription-panel-group change-plans-row">
            <div class="col-md-12 col-lg-8">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="portlet light bordered dashboard-panel">
                            <div class="portlet-body">
                                <div class="panel panel-default apple-backgound">
                                    <div class="panel-body">
                                        <div class="responsive-float-left subscription-panel-default-caption">
                                            <div><span id="change-plan-locations" class="bold"></span> Location(s)</div>
                                            <div><span id="change-plan-messages" class="bold"></span> Text Messages</div>
                                        </div>
                                        <div id="pricing-attr"
                                             class="responsive-float-right subscription-panel-large-caption">
                                            <sup class="subscription-panel-default-caption">$</sup><span id="change-plan-final-price"></span><sub class="subscription-panel-default-caption">/mo</sub>
                                            <div id="paid-annually-caption">
                                                <span id="annual-cost"></span><span>Paid Annually</span>
                                            </div>
                                        </div>
                                        <div id="contact-us" class="responsive-float-right subscription-panel-contact-us">
                                            <div>
                                                <div>Contact Us</div>
                                                <div>For Enterprise Pricing</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="portlet light bordered dashboard-panel">
                            <div class="portlet-title">
                                <div class="caption">
                                    <span>HOW MANY LOCATIONS DO YOU WANT?</span>
                                </div>
                            </div>
                            <div class="portlet-body add-locations">
                                <div class="col-sm-9 col-md-10">
                                    <input id="smsLocationSlider" type="text" />
                                </div>
                                <div class="col-sm-3 col-md-2">
                                    <div class="panel panel-default subscription-panel apple-backgound">
                                        <div class="panel-body">
                                            <div class="col-sm-12 col-md-12 slider-quota-caption">
                                                <div>
                                                    <div><span id="slider-locations" class="bold"></span></div>
                                                    <div>Location</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="portlet light bordered dashboard-panel">
                            <div class="portlet-title">
                                <div class="caption">
                                    <span>HOW MANY TEXT MESSAGES DO YOU NEED PER LOCATION?</span>
                                </div>
                            </div>
                            <div class="portlet-body add-messages">
                                <div class="col-sm-9 col-md-10">
                                    <input id="smsMessagesSlider" type="text" />
                                </div>
                                <div class="col-sm-3 col-md-2">
                                    <div class="panel panel-default subscription-panel apple-backgound">
                                        <div class="panel-body">
                                            <div class="col-sm-12 col-md-12 slider-quota-caption">
                                                <div>
                                                    <div><span id="slider-messages" class="bold"></span></div>
                                                    <div><span> Messages</span></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12 col-lg-4">
                <div class="row">
                    <div class="col-md-12 col-sm-12">
                        <div class="portlet light bordered dashboard-panel">
                            <div class="portlet-title">
                                <div class="caption">
                                    <span class="">Price Includes</span>
                                </div>
                            </div>
                            <div class="portlet-body">
                                {{ pricingDetails }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 col-sm-12">
                        <div class="portlet light bordered change-plan">
                            <div class="portlet-body">
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-md-12 col-sm-12">
                                            <div id="plan-type" class="btn-group btn-toggle subscription-toggle">
                                                <button class="btn active btn-primary" data-subscription='M'>Monthly</button>
                                                <button class="btn btn-default" data-subscription='Y'>Annually</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12 col-sm-12">
                                            <div class="growth-bar transparent center">
                                                <button id="submit-change-plan-btn" class="btn btn-block subscription-btn golden-poppy-backgound">Change Plan</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Change plan pop up -->
    <div class="modal fade" id="updatePlanModal" tabindex="-1" role="dialog" aria-labelledby="updatePlanModalLabel">
        <div class="change-plan modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="growth-bar">
                        <div class="caption">
                            <span>CHANGE PLAN SUCCEEDED!!!</span>
                        </div>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="panel panel-default apple-backgound">
                        <div class="panel-body">
                            <div class="responsive-float-left subscription-panel-default-caption">
                                <div><span id="modal-locations" class="bold"></span> Location(s)</div>
                                <div><span id="modal-messages" class="bold"></span> Text Messages</div>
                            </div>
                            <div class="responsive-float-right subscription-panel-large-caption">
                                <sup class="subscription-panel-default-caption">$</sup><span id="modal-price"></span><sub class="subscription-panel-default-caption">/mo</sub>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="form-group">
                            <button type="button" class="btn default golden-poppy-backgound subscription-btn" data-dismiss="modal">Finish</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script type="text/javascript">

    jQuery(document).ready(function ($) {

        var maxLocations = 100;
        var maxMessages = 1000;

        function getSubscriptionData() {
            return $('[id="subscription-data"]').data('subscription');
        }

        function initSubscriptionParameters() {
            var subscriptionData = getSubscriptionData();
        
            var currentPlanLocations = parseInt(subscriptionData.subscriptionPlan.locations);
            var currentPlanMessages = parseInt(subscriptionData.subscriptionPlan.sms_messages_per_location);

            /* Slider initializations */
            smsLocationSlider.setValue(currentPlanLocations, true, true);
            smsMessagesSlider.setValue(currentPlanMessages, true, true);

            /* Message init */
            $('#current-locations').text(smsLocationSlider.getValue());
            $('#change-plan-locations').text(smsLocationSlider.getValue());
            $('#slider-locations').text(smsLocationSlider.getValue());
            $('#modal-locations').text(smsLocationSlider.getValue());

            /* Locations init */
            $('#current-messages').text(smsMessagesSlider.getValue());
            $('#change-plan-messages').text(smsMessagesSlider.getValue());
            $('#slider-messages').text(smsMessagesSlider.getValue());
            $('#modal-messages').text(smsMessagesSlider.getValue());

            /* Calculate the initial plan value */
            refreshPlanValue();
        }

        function calculateMonthlyPlanCost() {
            
            /* Calculate the plan value */
            var subscriptionData = getSubscriptionData();
            
            /* Get number of locations and messages  */
            var locations = smsLocationSlider.getValue();
            var messages = smsMessagesSlider.getValue();
            
            /* 
             * Fetch the applicbale ranges for the calculations - we need the maximum values in an ordered sequence 
             * so we can limit to O(n) complexity and any complex branching. 
             *  
             */
            var range_maximums = Object.keys(subscriptionData.pricingPlanParameterLists);
            
            /* Calculate the total cost */
            var planCost = parseFloat(subscriptionData.pricingPlan.base_price);  
            var breakOnNextIteration = false;
            for (var i = 0; i < range_maximums.length; i++) {
            
                /* 
                 * Charge the next batch of locations, based on the current range, 
                 * and add it to the total
                 * 
                 */
                var parameterList = subscriptionData.pricingPlanParameterLists[range_maximums[i]];
                var nextBatchOfLocations = (parameterList.max_locations - parameterList.min_locations + 1);
                
                if ((locations - nextBatchOfLocations) <= 0) {  
                    nextBatchOfLocations = locations;
                    breakOnNextIteration = true; 
                } else {
                    locations -= nextBatchOfLocations;
                }
                
                var cost = parseFloat(nextBatchOfLocations * messages * subscriptionData.pricingPlan.charge_per_sms);
                cost *= ((100 - parseFloat(parameterList.location_discount_percentage)) * 0.01); 
                        
                planCost += cost;
                
                if(breakOnNextIteration) {
                    break;
                }
               
            }
            
            return planCost;
        }
        
        function applyAnnualDiscount(monthlyPlanCost) {
            
            /*
             * If the yearly plan is selected, calculate the current plan cost over 12 months, apply the annual
             * discount, and divide by 12 to get the monthly payment. 
             * 
             */
            var subscriptionData = getSubscriptionData();

            return Math.round(monthlyPlanCost * ((100 - parseFloat(subscriptionData.pricingPlan.annual_discount)) * 0.01)); 
        }
        
        function refreshPlanValue() {
            
            /* Get elements */
            var priceDisplay = document.getElementById("change-plan-final-price");
            var modalPriceDisplay = document.getElementById("modal-price");

            var monthlyPlanCost = calculateMonthlyPlanCost();
            
            var planType = $(document.getElementById("plan-type")).find('.btn-primary').text();
            if (planType === 'Annually') {
                monthlyPlanCost = applyAnnualDiscount(monthlyPlanCost);
                $('#annual-cost').text('$' + monthlyPlanCost.toFixed(2));
                $('#paid-annually-caption').show()
            } else {
                $('#paid-annually-caption').hide();
            }
            
            $(priceDisplay).text(Math.round(monthlyPlanCost).toFixed(2));
            $(modalPriceDisplay).text(Math.round(monthlyPlanCost).toFixed(2));
            
        };

        function getSubscriptionParams() {
            var locations = smsLocationSlider.getValue();
            var messages = smsMessagesSlider.getValue();
            var planType = getSubscriptionData().subscriptionPlan.payment_plan;

            var price = $(document.getElementById("change-plan-final-price")).text();
            if (planType === 'Annually') {
                price = $('#annual-cost').text('$' + price.toFixed(2)).substring(1); // Strip the leading dollar sign
            }
            ;
            return {locations: locations, messages: messages, planType: planType, price: price};
        };

        function changePlan() {
            $.post('/businessSubscription/changePlan', getSubscriptionParams())
                    .done(function (data) {
                        if (data.status === true) {
                            $('#updatePlanModal').modal('show');
                        } else {
                            alert("Change plan failed!!!");
                        }
                    })
                    .fail(function () {})
                    .always(function () {});
        }

        function updateLargeCaption(current, max) {
            if (current > max) {
                $('#contact-us').show();
                $('#pricing-attr').hide();
                $('#submit-change-plan-btn').prop('disabled', true);
            } else {
                $('#contact-us').hide();
                $('#pricing-attr').show();
                $('#submit-change-plan-btn').prop('disabled', false);
            }
        }

        var smsLocationSlider = new Slider("#smsLocationSlider", {
            tooltip: 'show',
            min: 1,
            max: maxLocations + 1,
            step: 1,
            ticks: [1, 10, 20, 30, 40, 50, 60, 70, 80, 90, 101],
            ticks_labels: [
                '<div>1</div><div class="tick-marker">|</div>',
                '<div>10</div><div class="tick-marker">|</div>',
                '<div>20</div><div class="tick-marker">|</div>',
                '<div>30</div><div class="tick-marker">|</div>',
                '<div>40</div><div class="tick-marker">|</div>',
                '<div>50</div><div class="tick-marker">|</div>',
                '<div>60</div><div class="tick-marker">|</div>',
                '<div>70</div><div class="tick-marker">|</div>',
                '<div>80</div><div class="tick-marker">|</div>',
                '<div>90</div><div class="tick-marker">|</div>',
                '<div>100</div><div class="tick-marker">|</div>'
            ],
            ticks_snap_bounds: 1
        });

        var smsMessagesSlider = new Slider("#smsMessagesSlider", {
            tooltip: 'show',
            min: 100,
            max: maxMessages + 1,
            step: 50,
            ticks: [100, 150, 200, 250, 300, 350, 400, 450, 500, 550, 600, 650, 700, 750, 800, 850, 900, 950, 1001],
            ticks_labels: [
                '<div>100</div><div class="tick-marker">|</div>',
                '<div>150</div><div class="tick-marker">|</div>',
                '<div>200</div><div class="tick-marker">|</div>',
                '<div>250</div><div class="tick-marker">|</div>',
                '<div>300</div><div class="tick-marker">|</div>',
                '<div>350</div><div class="tick-marker">|</div>',
                '<div>400</div><div class="tick-marker">|</div>',
                '<div>450</div><div class="tick-marker">|</div>',
                '<div>500</div><div class="tick-marker">|</div>',
                '<div>550</div><div class="tick-marker">|</div>',
                '<div>600</div><div class="tick-marker">|</div>',
                '<div>650</div><div class="tick-marker">|</div>',
                '<div>700</div><div class="tick-marker">|</div>',
                '<div>750</div><div class="tick-marker">|</div>',
                '<div>800</div><div class="tick-marker">|</div>',
                '<div>850</div><div class="tick-marker">|</div>',
                '<div>900</div><div class="tick-marker">|</div>',
                '<div>950</div><div class="tick-marker">|</div>',
                '<div>1000</div><div class="tick-marker">|</div>'
            ],
            ticks_snap_bounds: 1
        });

        smsLocationSlider.on('change', function (values) {
            updateLargeCaption(values.newValue, maxLocations);
            refreshPlanValue();
            
            if (values.newValue > maxLocations) {
                values.newValue = maxLocations + '+';
            }
            $('#change-plan-locations').text(values.newValue);
            $('#slider-locations').text(values.newValue);
            $('#modal-locations').text(values.newValue);
        });
        smsMessagesSlider.on('change', function (values) {
            refreshPlanValue();
            updateLargeCaption(values.newValue, maxMessages);
            if (values.newValue > maxMessages) {
                values.newValue = maxMessages + '+';
            }
            $('#change-plan-messages').text(values.newValue);
            $('#slider-messages').text(values.newValue);
            $('#modal-messages').text(values.newValue);
            
        });

        $('.subscription-toggle').click(function () {
            $(this).find('.btn').toggleClass('active');
            
            if ($(this).find('.btn-primary').size() > 0) {
                $(this).find('.btn').toggleClass('btn-primary');
            }

            $(this).find('.btn').toggleClass('btn-default');

            refreshPlanValue();
        });
        
        $('#submit-change-plan-btn').click(function () {
            changePlan();
        });

        initSubscriptionParameters();

    });
</script>