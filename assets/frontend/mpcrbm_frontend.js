(function ($) {
  "use strict";

  $(document).ready(function () {
    $(document).ready(function () {
      $(document).on(
        "change",
        "#mpcrbm_shopping_number, #mpcrbm_passenger_number",
        function () {
          let shoppingNumber = parseInt($("#mpcrbm_shopping_number").val());
          let passengerNumber = parseInt($("#mpcrbm_passenger_number").val());

          let elements = document.querySelectorAll(
            "*[class*='feature_passenger_']"
          );

          elements.forEach(function (element) {
            let classList = element.classList;
            let passengerComparisonNumber = 0;
            let bagComparisonNumber = 0;

            // Find the comparison numbers from the class name
            classList.forEach(function (className) {
              if (className.startsWith("feature_passenger_")) {
                const parts = className.split("_");

                console.log("Parts:", parts); // Check the parts array to ensure correct splitting

                passengerComparisonNumber = parseInt(parts[2], 10); // Index 2 for feature passenger number
                bagComparisonNumber = parseInt(parts[5], 10); // Index 5 for feature bag number
                post_id = parseInt(parts[8], 10); // Index 8 for ID
              }
            });

            // Toggle display based on comparison
            if (
              shoppingNumber > bagComparisonNumber ||
              passengerNumber > passengerComparisonNumber
            ) {
              $(element).hide(300); // Hide the element
            } else {
              $(element).show(300); // Show the element
            }
          });
        }
      );
    });


    var mpcrbmTemplateExists = $(".mpcrbm-show-search-result").length;
    
    if (mpcrbmTemplateExists) {
      
      $(".mpcrbm_order_summary").css("display", "none");
      function getCookiesWithPrefix(prefix) {
        const cookies = document.cookie.split(";");
        const filteredCookies = cookies.filter((cookie) =>
          cookie.trim().startsWith(prefix)
        );
        return filteredCookies.map((cookie) => cookie.trim().split("=")[0]);
      }
      const cookieIds = getCookiesWithPrefix(".mpcrbm_booking_item_");

      function addClassFromElements() {
        $(".mpcrbm_booking_item").each(function () {
          const $this = $(this);
          let hasCookieId = false;
          for (let i = 0; i < cookieIds.length; i++) {
            document.cookie = `${cookieIds[i]}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
            if ($this.hasClass(cookieIds[i].substring(1))) {
              hasCookieId = true;
              break;
            }
          }
          if (!hasCookieId) {
            $this.addClass("mpcrbm_booking_item_hidden");
          }
        });
      }

      // Call the function to add the class
      addClassFromElements();
    }
  });
})(jQuery);
