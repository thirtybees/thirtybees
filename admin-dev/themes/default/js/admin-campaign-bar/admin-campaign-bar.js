(function() {

    var setCampaignChangeIntervalVar = 0;

    /// Campaign Bar Variables ///
    var campaigns = [
        {
            class: 'campaign_bar_love_class',
            intro: 'campaign_bar_love_intro',
            cta: 'campaign_bar_love_cta',
            url: 'campaign_bar_love_url'
        },
        {
            class: 'campaign_bar_techsupport_class',
            intro: 'campaign_bar_techsupport_intro',
            cta: 'campaign_bar_techsupport_cta',
            url: 'campaign_bar_techsupport_url'
        },
        {
            class: 'campaign_bar_thanks_class',
            intro: 'campaign_bar_thanks_intro',
            cta: 'campaign_bar_thanks_cta',
            url: 'campaign_bar_thanks_url'
        },
        {
            class: 'campaign_bar_premium_class',
            intro: 'campaign_bar_premium_intro',
            cta: 'campaign_bar_premium_cta',
            url: 'campaign_bar_premium_url'
        }
    ];

    /// Campaign Slider Variables ///
    var campaignsSlider = [
        {
            class: 'campaign_slider_love_class',
            header: 'campaign_slider_love_header',
            intro: 'campaign_slider_love_intro',
            cta: 'campaign_slider_love_cta',
            url: 'campaign_slider_love_url'
        },
        {
            class: 'campaign_slider_techsupport_class',
            header: 'campaign_slider_techsupport_header',
            intro: 'campaign_slider_techsupport_intro',
            cta: 'campaign_slider_techsupport_cta',
            url: 'campaign_slider_techsupport_url'
        },
        {
            class: 'campaign_slider_thanks_class',
            header: 'campaign_slider_thanks_header',
            intro: 'campaign_slider_thanks_intro',
            cta: 'campaign_slider_thanks_cta',
            url: 'campaign_slider_thanks_url'
        },
        {
            class: 'campaign_slider_premium_class',
            header: 'campaign_slider_premium_header',
            intro: 'campaign_slider_premium_intro',
            cta: 'campaign_slider_premium_cta',
            url: 'campaign_slider_premium_url'
        }
    ];

    /// Get Random Campaign Bar ///
    var previousCampaign;

    function getRandomCampaignBar() {
        var randomCampaign;
        do {
            randomCampaign = campaigns[Math.floor(Math.random() * campaigns.length)];
        } while (randomCampaign === previousCampaign);

        previousCampaign = randomCampaign;
        return randomCampaign;
    }

    function getRandomCampaignSlider() {
        var randomCampaign;
        do {
            randomCampaign = campaignsSlider[Math.floor(Math.random() * campaignsSlider.length)];
        } while (randomCampaign === previousCampaignSlider);

        previousCampaignSlider = randomCampaign; // Update the previous campaign
        return randomCampaign;
    }

    /// Update Campaign Bar ///
    function updateCampaignBar(campaign) {
        /// Check if campaign is defined
        if (campaign && campaign.intro) {
            console.log(campaign);
            /// Update the campaign bar with the selected campaign
            $('.tb-admin-campaign-bar-text-inner').html(window[campaign.intro]);
            $('.tb-admin-campaign-bar-cta-inline a').html(window[campaign.cta]);
            $('.tb-admin-campaign-bar-cta-inline a').attr("href", window[campaign.url]);
            $('.tb-admin-campaign-bar-cta a').html(window[campaign.cta]);
            $('.tb-admin-campaign-bar-cta a').attr("href", window[campaign.url]);

            /// Remove previous class and add the selected class
            $('.tb-admin-campaign-bar').removeClass().addClass('tb-admin-campaign-bar ' + window[campaign.class]);
            var checkForTechSupportClass = $('.tb-admin-campaign-bar').hasClass("campaign-bar-technical-support");
            if (checkForTechSupportClass) {
                $(".campaign-bar-technical-support .tb-admin-campaign-bar-cta a").attr("target", "_blank");
                $(".campaign-bar-technical-support .tb-admin-campaign-bar-cta-inline a").attr("target", "_blank");
            } else {
                $(".tb-admin-campaign-bar-cta a").attr("target", "_self");
                $(".tb-admin-campaign-bar-cta-inline a").attr("target", "_self");
            }

            $(".campaign-bar-holder").removeClass("animate-campaign-bar-out");

            $(".campaign-bar-holder").addClass("animate-campaign-bar-in");
            bindTopBarSupporterCampaignModal(campaign); /// Bind Supporter Modal
        } else {
            var newCampaign = getRandomCampaignBar();
            updateCampaignBar(newCampaign);
        }
    }

    /// Bind/Unbind TopBar Supporter Campaign Modal ///
    function bindTopBarSupporterCampaignModal(campaign) {
        if (campaign.class === 'campaign_bar_love_class') {
            setTimeout(function () {
                $('.campaign-bar-supporter .campaign-bar-holder-inner-actual').on("click", function () {
                    openSupportThirtyBeesModal();
                });
            }, 10);
        } else {
            $(".tb-admin-campaign-bar .campaign-bar-holder-inner-actual").off('click');
        }
    }

    /// Bind/Unbind Slider Supporter Campaign Modal ///
    function bindSliderSupporterCampaignModal(campaign) {
        if (campaign.class === 'campaign_slider_love_class') {
            setTimeout(function () {
                $('.campaign-slider-holder.campaign-slider-supporter .campaign-slider-holder-inner-actual').on("click", function () {
                    openSupportThirtyBeesModal();
                });
            }, 10);
        } else {
            $(".campaign-slider-holder .campaign-slider-holder-inner-actual").off('click');
        }
    }

    /// Check Admin BG Colour ///
    function checkAdminBGColour() {
        function adminHeaderDark() {
            $(".bootstrap #header_infos").addClass("admin-header-dark");
        }

        function adminHeaderLight() {
            $(".bootstrap #header_infos").addClass("admin-header-light");
        }

        var getAdminBGColour = $(".bootstrap #header_infos").css('background-color');
        /// Dark
        if (getAdminBGColour === "rgb(119, 41, 83)" || getAdminBGColour === "rgb(40, 43, 48)") {
            adminHeaderDark();
        }
        /// Light
        if (getAdminBGColour === "rgb(255, 204, 0)") {
            adminHeaderLight();
        }
    }

    /// Initiate Campaign Bar ///
    function initiateCampaignBar(setCampaignBarStartDelay, setCampaignChangeInterval) {
        setCampaignChangeIntervalVar = setCampaignChangeInterval;
        if (setCampaignChangeIntervalVar == null || setCampaignChangeInterval === undefined) {
            setCampaignChangeIntervalVar = 20000; /// Default campaign change duration
        }
        checkAdminBGColour();

        function updateAndAnimate() {
            var newCampaign = getRandomCampaignBar();

            $(".campaign-bar-holder").addClass("animate-campaign-bar-out");

            setTimeout(function () {
                $(".campaign-bar-holder").removeClass("animate-campaign-bar-out");
                updateCampaignBar(newCampaign);
            }, 2000);
        }

        /// Start Delay
        setTimeout(function () {
            $(".campaign-bar-holder").css("visibility", "visible");

            /// Initial update
            var initialCampaign = getRandomCampaignBar();
            updateCampaignBar(initialCampaign);

            /// Update every x seconds
            setInterval(updateAndAnimate, setCampaignChangeIntervalVar);
        }, setCampaignBarStartDelay);
    }

    /// Update Campaign Slider ///
    var previousCampaignSlider; // Define the previous campaign variable
    function updateCampaignSlider(campaign) {

        if (campaign && campaign.intro) {
            $('.tb-admin-campaign-slider-header-inner').html(window[campaign.header]);
            $('.tb-admin-campaign-slider-text-inner').html(window[campaign.intro]);
            $('.tb-admin-campaign-slider-cta-inline a').html(window[campaign.cta]);
            $('.tb-admin-campaign-slider-cta-inline a').attr("href", window[campaign.url]);
            $('.tb-admin-campaign-slider-cta a').html(window[campaign.cta]);
            $('.tb-admin-campaign-slider-cta a').attr("href", window[campaign.url]);

            $('.campaign-slider-holder').removeClass().addClass('campaign-slider-holder ' + window[campaign.class]);

            var checkForTechSupportClassSlider = $('.campaign-slider-holder').hasClass("campaign-slider-technical-support");
            if (checkForTechSupportClassSlider) {
                $(".campaign-slider-technical-support .tb-admin-campaign-slider-cta a").attr("target", "_blank");
                $(".campaign-slider-technical-support .tb-admin-campaign-slider-cta-inline a").attr("target", "_blank");
            } else {
                $(".tb-admin-campaign-slider-cta a").attr("target", "_self");
                $(".tb-admin-campaign-slider-cta-inline a").attr("target", "_self");
            }

            // Animation
            $(".campaign-slider-holder").removeClass("animate-campaign-slider-in");
            $(".campaign-slider-holder").addClass("animate-campaign-slider-out");

            setTimeout(function () {
                $(".campaign-slider-holder").removeClass("animate-campaign-slider-out");
                $(".campaign-slider-holder").addClass("animate-campaign-slider-in");
            }, 250);

            bindSliderSupporterCampaignModal(campaign);
            $(".campaign-slider-close-holder-inner").off("click");
            campaignSliderClose();

        } else {
            var newCampaignSlider = getRandomCampaignSlider();
            updateCampaignSlider(newCampaignSlider); /// Update with a new campaign
        }
    }

    /// Initiate Campaign Slider ///
    function initiateCampaignSlider(setCampaignSliderStartDelay, setSliderCampaignChangeInterval) {
        var setSliderCampaignChangeIntervalVar = setSliderCampaignChangeInterval || 8000;

        function updateAndAnimateSlider() {
            var newCampaignSlider = getRandomCampaignSlider();
            updateCampaignSlider(newCampaignSlider);
        }

        setTimeout(function () {
            $(".campaign-slider-holder").attr("style", "visibility: visible!important; z-index: 999!important;");
            /// Initial update
            var initialCampaign = getRandomCampaignSlider();
            updateCampaignSlider(initialCampaign);

            /// Define an array of classes to randomly apply
            var randomClasses = ['animate-campaign-slider-in-right', 'animate-campaign-slider-flip-center', 'animate-campaign-slider-flip-rightleft', 'animate-campaign-slider-in-bottom'];
            var randomClass = randomClasses[Math.floor(Math.random() * randomClasses.length)];
            $('.campaign-slider-holder').removeClass('campaign-slider-hide');
            $('.campaign-slider-holder').addClass(randomClass);

            // Update every x seconds
            setInterval(updateAndAnimateSlider, setSliderCampaignChangeIntervalVar);
        }, setCampaignSliderStartDelay);
    }

    /// Check for notifications and show on responsive bell icon ///
    function checkForNotifications() {
        setTimeout(function () {
            $('.tb-admin-campaign-bar .notifs_badge span').each(function () {
                var notificationCount = $(this).text();
                if (notificationCount > 0) {
                    $(".tb-admin-campaign-bar-fa-icon .notifs_badge").css("display", "inline-flex");
                }
            });
        }, 2000);
    }

    /// Notification code grab and inject into modal + open
    function openNotificationsModal() {
        /// Clone the specific element you want to inject
        var contentToInject = $('#header_notifs_icon_wrapper').clone();

        /// Remove the ID attribute to prevent duplication
        contentToInject.removeAttr('id');

        /// Inject the cloned content into the modal
        $('#notificationsModalContent').html(contentToInject);

        /// Show the modal
        $('#notificationsModal').modal('show');

        /// Clears the modal of content to prevent possible issues with duplicate IDs
        $('#notificationsModal').on('hidden.bs.modal', function () {
            $('#notificationsModalContent').html("");
        });
    }

    /// Support ThirtyBees Modal ///
    function openSupportThirtyBeesModal() {
        /// Show the modal
        $('#supportThirtyBeesModal').modal('show');
    }


    /// Make notification ///
    function makeNotification(content) {
        // Create the toast
        $(".campaign-notification").addClass("campaign-message-slide-in");
        $(".campaign-notification-text").html(content);
        setTimeout(function () {
            $(".campaign-notification").removeClass("campaign-message-slide-in");
            $(".campaign-notification").addClass("campaign-message-slide-out");
        }, 6500);
        setTimeout(function () {
            $(".campaign-notification").removeClass("campaign-message-slide-out");
            $(".campaign-notification").removeClass("campaign-message-slide-in");
        }, 6800);

    }

    function hideCampaigns() {
        $.ajax({
            url: "index.php",
            cache: false,
            dataType: 'json',
            data: "token=" + employee_token + '&ajax=1&action=disableCampaign&controller=AdminEmployees',
            success: function (data) {
                if (data.status === 'success') {
                    makeNotification(data.data);
                    $(".campaign-bar-holder").addClass("tb-campaign-bar-fade-out");
                    $(".campaign-slider-holder-outer").fadeOut(250);
                    setTimeout(function () {
                        $('body').removeClass('show-campaign-bar');
                        $('body').removeClass('show-campaign-slider');
                    }, 2000);
                } else {
                    if (data.message) {
                        makeNotification(data.message);
                    } else {
                        makeNotification('Error');
                    }
                }
            },
            error: function(resp) {
                if (resp.responseJSON && resp.responseJSON.message) {
                    makeNotification(resp.responseJSON.message);
                } else {
                    makeNotification('Error');
                }
            }
        });
    }

    function openSupportThirtyBeesCloseModal() {
        if (!isSupporter()) {
            $('#supportThirtyBeesCloseModal').modal('show');
            $('.setTopBarModal1Month').on("click", function() {
                $('#supportThirtyBeesCloseModal').modal('hide');
                hideCampaigns();
            });
        } else {
            hideCampaigns();
        }
    }

    /// Binds the Close functionality and logic for the Top Bar Campaign Slider
    function campaignBarClose() {
        $(".campaign-bar-close-holder").off("click");
        $(".campaign-bar-close-holder").on("click", openSupportThirtyBeesCloseModal);
    }

    /// Binds the Close functionality and logic for the Campaign Slider
    function campaignSliderClose() {
        $(".campaign-slider-close-holder-inner").off("click");
        $(".campaign-slider-close-holder-inner").on("click", openSupportThirtyBeesCloseModal);
    }

    /// Inits the Campaign Bar and Slider ///
    function campaignBarSliderInits() {
        /// Check if the user is a member ///
        if (isSupporter()) {

            var thanksCampaign = campaigns.find(function (campaign) {
                return campaign.class === 'campaign_bar_thanks_class';
            });

            if (thanksCampaign) {
                /// If the campaign is found, update the campaign bar
                checkAdminBGColour();
                campaignBarClose(); /// Binds Campaign Bar Close
                $(".campaign-bar-holder").css("visibility", "visible");
                $(".campaign-bar-holder").addClass("animate-campaign-bar-in");
                updateCampaignBar(thanksCampaign);
            }
            var thanksCampaignSlider = campaignsSlider.find(function (campaignsSlider) {
                return campaignsSlider.class === 'campaign_slider_thanks_class';
            });

            if (thanksCampaignSlider) {
                $(".campaign-slider-holder").attr("style", "visibility: visible!important; z-index: 999!important;");
                var randomClasses = ['animate-campaign-slider-in-right', 'animate-campaign-slider-flip-center', 'animate-campaign-slider-flip-rightleft', 'animate-campaign-slider-in-bottom'];
                var randomClass = randomClasses[Math.floor(Math.random() * randomClasses.length)];
                $('.campaign-slider-holder').removeClass('campaign-slider-hide');
                $('.campaign-slider-holder').addClass(randomClass);
                updateCampaignSlider(thanksCampaignSlider);
            }
        } else {
            initiateCampaignBar(5000, 20000); /// Start delay, Cycle delay
            initiateCampaignSlider(10000, 20000); /// Start delay, Cycle delay
            campaignBarClose(); /// Binds Campaign Bar Close
            campaignSliderClose(); /// Binds Campaign Slider Close
        }
    }

    function isSupporter() {
        return (typeof window['supporter_info'] === 'object') && window['supporter_info']['type'];
    }

    $(document).ready(function () {
        campaignSliderClose();
        checkForNotifications();
        $('.notifications-icon').on('click', openNotificationsModal);
        campaignBarSliderInits();
    });
})();