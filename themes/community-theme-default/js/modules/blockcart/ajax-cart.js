/* global generated_date, baseDir, contentOnly, fieldRequired, freeProductTranslation, delete_txt,
 orderProcess, CUSTOMIZE_TEXTFIELD, customizationIdMessage, freeShippingTranslation, toBeDetermined */
$(function() {

  ajaxCart.overrideButtonsInThePage();

  var current_timestamp = parseInt(new Date().getTime() / 1000);

  if (typeof $('.ajax_cart_quantity').html() == 'undefined' || (typeof generated_date != 'undefined' && generated_date != null && (parseInt(generated_date) + 30) < current_timestamp))
    ajaxCart.refresh();

  /** Hover observers */
  var oBlockcartDropDown = new HoverWatcher('#blockcart-dropdown');
  var oBlockcart         = new HoverWatcher('#blockcart');
  var $cartDropDown      = $('#blockcart-dropdown');
  var $cartHeader        = $('#blockcart-header');
  var is_touch_enabled   = ('ontouchstart' in document.documentElement);

  var hoverCollapseTimeout = 200;

  $cartHeader.on({
    mouseenter: function() {
      if (ajaxCart.nb_total_products > 0 || parseInt($('.ajax_cart_quantity').html()) > 0)
        $cartDropDown.stop(true, true).slideDown();
    },
    mouseleave: function() {
      setTimeout(function() {
        if (!oBlockcart.isHoveringOver() && !oBlockcartDropDown.isHoveringOver())
          $cartDropDown.stop(true, true).slideUp();
      }, hoverCollapseTimeout);
    },
    click: function(e) {
      e.preventDefault();
      e.stopPropagation();

      // Simulate hover when browser says device is touch based
      if (is_touch_enabled) {
        if ($(this).next('.cart_block:visible').length && !oBlockcartDropDown.isHoveringOver()) {
          $cartDropDown.stop(true, true).slideUp();
        } else if (ajaxCart.nb_total_products > 0 || parseInt($('.ajax_cart_quantity').html()) > 0) {
          $cartDropDown.stop(true, true).slideDown();
        }
        return false;
      } else {
        window.location.href = $(this).attr('href');
      }
    }
  });

  $cartDropDown.on('mouseleave', function() {
    setTimeout(function() {
      if (!oBlockcart.isHoveringOver()) {
        $cartDropDown.stop(true, true).slideUp();
      }
    }, hoverCollapseTimeout);
  });

  $(document).on('click', '.delete_voucher', function(e) {
    e.preventDefault();
    $.ajax({
      type: 'POST',
      headers: {'cache-control': 'no-cache'},
      async: true,
      cache: false,
      url: $(this).attr('href') + '?rand=' + new Date().getTime()
    });
    $(this).parent().parent().remove();
    ajaxCart.refresh();
    var bodyId = $('body').attr('id');
    if (bodyId == 'order' || bodyId == 'order-opc') {
      if (typeof(updateAddressSelection) != 'undefined') {
        updateAddressSelection();
      } else {
        window.location.reload();
      }
    }
  });

  $(document).on('click', '#cart_navigation input', function() {
    $(this).prop('disabled', 'disabled').addClass('disabled');
    $(this).closest('form').get(0).submit();
  });

  $(document).on('click', '#layer_cart .cross, #layer_cart .continue, .layer_cart_overlay', function(e) {
    e.preventDefault();
    $('.layer_cart_overlay').hide();
    $('#layer_cart').fadeOut('fast');
  });

  $('#columns #layer_cart, #columns .layer_cart_overlay').detach().prependTo('#columns');
});

//JS Object : update the cart by ajax actions
var ajaxCart = {

  nb_total_products: 0,

  //override every button in the page in relation to the cart
  overrideButtonsInThePage: function() {
    //for every 'add' buttons...
    $(document).off('click', '.ajax_add_to_cart_button').on('click', '.ajax_add_to_cart_button', function(e) {
      e.preventDefault();
      var idProduct          = parseInt($(this).data('id-product'));
      var idProductAttribute = parseInt($(this).data('id-product-attribute'));
      var minimalQuantity    = parseInt($(this).data('minimal_quantity')) || 1;

      if ($(this).prop('disabled') != 'disabled')
        ajaxCart.add(idProduct, idProductAttribute, false, this, minimalQuantity);
    });
    //for product page 'add' button...
    if ($('.cart_block').length) {
      $(document).off('click', '#add_to_cart button').on('click', '#add_to_cart button', function(e) {
        e.preventDefault();
        ajaxCart.add($('#product_page_product_id').val(), $('#idCombination').val(), true, null, $('#quantity_wanted').val(), null);
      });
    }

    //for 'delete' buttons in the cart block...
    $(document).off('click', '.cart_block_list .ajax_cart_block_remove_link').on('click', '.cart_block_list .ajax_cart_block_remove_link', function(e) {
      e.preventDefault();
      // Customized product management
      var customizationId = 0;
      var productId = 0;
      var productAttributeId = 0;
      var customizableProductDiv = $($(this).parent().parent()).find('div[data-id^=deleteCustomizableProduct_]');
      var idAddressDelivery = false;

      if (customizableProductDiv && $(customizableProductDiv).length) {
        var ids = customizableProductDiv.data('id').split('_');
        if (typeof(ids[1]) != 'undefined') {
          customizationId = parseInt(ids[1]);
          productId = parseInt(ids[2]);
          if (typeof(ids[3]) != 'undefined')
            productAttributeId = parseInt(ids[3]);
          if (typeof(ids[4]) != 'undefined')
            idAddressDelivery = parseInt(ids[4]);
        }
      }

      // Common product management
      if (!customizationId) {
        //retrieve idProduct and idCombination from the displayed product in the block cart
        var firstCut = $(this).parent().parent().data('id').replace('cart_block_product_', '');
        firstCut = firstCut.replace('deleteCustomizableProduct_', '');
        ids = firstCut.split('_');
        productId = parseInt(ids[0]);

        if (typeof(ids[1]) != 'undefined')
          productAttributeId = parseInt(ids[1]);
        if (typeof(ids[2]) != 'undefined')
          idAddressDelivery = parseInt(ids[2]);
      }

      // Removing product from the cart
      ajaxCart.remove(productId, productAttributeId, customizationId, idAddressDelivery);
    });
  },

  // try to expand the cart
  expand: function() {
    // disabled
  },

  // try to collapse the cart
  collapse: function() {
    // disabled
  },
  // Fix display when using back and previous browsers buttons
  refresh: function() {
    $.ajax({
      type: 'POST',
      headers: {'cache-control': 'no-cache'},
      url: (typeof(baseUri) !== 'undefined') ? baseUri + '?rand=' + new Date().getTime() : '',
      async: true,
      cache: false,
      dataType: 'json',
      data: (typeof(static_token) !== 'undefined') ? 'controller=cart&ajax=true&token=' + static_token : '',
      success: function(jsonData) {
        ajaxCart.updateCart(jsonData);
      }
    });
  },

  // Update the cart information
  updateCartInformation: function(jsonData, addedFromProductPage) {
    ajaxCart.updateCart(jsonData);
    var $productPageBtn = $('#add_to_cart').find('button');
    // reactive the button when adding has finished
    if (addedFromProductPage) {
      $productPageBtn.removeProp('disabled').removeClass('disabled');
      $productPageBtn.toggleClass('added', !jsonData.hasError || jsonData.hasError == false);
    } else
      $('.ajax_add_to_cart_button').removeProp('disabled');
  },

  // close fancybox
  updateFancyBox: function() {},

  // add a product in the cart via ajax
  add: function(idProduct, idCombination, addedFromProductPage, callerElement, quantity, whishlist) {

    if (addedFromProductPage && !checkCustomizations()) {
      if (contentOnly) {
        window.parent.location.href = window.location.href.replace('content_only=1', '');
        return;
      }
      PrestaShop.showError(fieldRequired);
      return;
    }

    var $productPageBtn = $('#add_to_cart').find('button');

    //disabled the button when adding to not double add if user double click
    if (addedFromProductPage) {
      $productPageBtn.prop('disabled', 'disabled').addClass('disabled');
      $('.filled').removeClass('filled');
    } else
      $(callerElement).prop('disabled', 'disabled');

    //send the ajax request to the server

    $.ajax({
      type: 'POST',
      headers: {'cache-control': 'no-cache'},
      url: baseUri + '?rand=' + new Date().getTime(),
      async: true,
      cache: false,
      dataType: 'json',
      data: 'controller=cart&add=1&ajax=true&qty=' + ((quantity && quantity != null) ? quantity : '1') + '&id_product=' +
      idProduct + '&token=' + static_token + ((parseInt(idCombination) && idCombination != null) ? '&ipa=' +
      parseInt(idCombination) : '' + '&id_customization=' + ((typeof customizationId !== 'undefined') ? customizationId : 0)),

      /**
       * @param {{ errors, hasError, crossSelling, products }} jsonData
       */
      success: function(jsonData) {
        // add appliance to wishlist module
        if (whishlist && !jsonData.errors) {
          WishlistAddProductCart(whishlist[0], idProduct, idCombination, whishlist[1]);
        }

        if (!jsonData.hasError) {
          if (contentOnly) {
            window.parent.ajaxCart.updateCartInformation(jsonData, addedFromProductPage);
          } else {
            ajaxCart.updateCartInformation(jsonData, addedFromProductPage);
          }

          if (jsonData.crossSelling) {
            $('.crossseling').html(jsonData.crossSelling);
          }

          if (idCombination) {
            $(jsonData.products).each(function() {
              if (this.id != undefined && this.id == parseInt(idProduct) && this.idCombination == parseInt(idCombination)) {
                if (contentOnly) {
                  window.parent.ajaxCart.updateLayer(this);
                } else {
                  ajaxCart.updateLayer(this);
                }
              }
            });
          } else {
            $(jsonData.products).each(function() {
              if (this.id != undefined && this.id == parseInt(idProduct)) {
                if (contentOnly) {
                  window.parent.ajaxCart.updateLayer(this);
                } else {
                  ajaxCart.updateLayer(this);
                }
              }
            });
          }

          if (contentOnly) {
            parent.$.fancybox.close();
          }

        } else {
          if (contentOnly) {
            window.parent.ajaxCart.updateCart(jsonData);
          } else {
            ajaxCart.updateCart(jsonData);
          }

          if (addedFromProductPage) {
            $productPageBtn.removeProp('disabled').removeClass('disabled');
          } else {
            $(callerElement).removeProp('disabled');
          }
        }

        emptyCustomizations();

      },
      error: function(XMLHttpRequest, textStatus, errorThrown) {
        PrestaShop.showError(
          'Impossible to add the product to the cart.<br/>textStatus: \'' + textStatus + '\'<br/>errorThrown: \'' +
          errorThrown + '\'<br/>responseText:<br/>' + XMLHttpRequest.responseText
        );
        // Reactivate the button when adding has finished
        if (addedFromProductPage) {
          $productPageBtn.removeProp('disabled').removeClass('disabled');
        } else {
          $(callerElement).removeProp('disabled');
        }
      }
    });
  },

  //remove a product from the cart via ajax
  remove: function(idProduct, idCombination, customizationId, idAddressDelivery) {
    //send the ajax request to the server
    $.ajax({
      type: 'POST',
      headers: {'cache-control': 'no-cache'},
      url: baseUri + '?rand=' + new Date().getTime(),
      async: true,
      cache: false,
      dataType: 'json',
      data: 'controller=cart&delete=1&id_product=' + idProduct + '&ipa=' + ((idCombination != null && parseInt(idCombination)) ? idCombination : '') +
      ((customizationId && customizationId != null) ? '&id_customization=' + customizationId : '') + '&id_address_delivery=' +
      idAddressDelivery + '&token=' + static_token + '&ajax=true',
      success: function(jsonData) {
        ajaxCart.updateCart(jsonData);
        var bodyId = $('body').attr('id');
        if (bodyId == 'order' || bodyId == 'order-opc') {
          deleteProductFromSummary(idProduct + '_' + idCombination + '_' + customizationId + '_' + idAddressDelivery);
        }
      },
      error: function() {
        PrestaShop.showError('ERROR: unable to delete the product');
      }
    });
  },

  //hide the products displayed in the page but no more in the json data
  hideOldProducts: function(jsonData) {
    //delete an eventually removed product of the displayed cart (only if cart is not empty!)
    if ($('.cart_block_list:first dl.products').length > 0) {
      var removedProductId = null;

      //look for a product to delete...
      $('.cart_block_list:first dl.products dt').each(function() {
        //retrieve idProduct and idCombination from the displayed product in the block cart
        var domIdProduct = $(this).data('id');
        var firstCut = domIdProduct.replace('cart_block_product_', '');
        var ids = firstCut.split('_');

        //try to know if the current product is still in the new list
        var stayInTheCart = false;
        for (var aProduct in jsonData.products) {
          if (jsonData.products.hasOwnProperty(aProduct)) {
            //we've called the variable aProduct because IE6 bug if this variable is called product
            //if product has attributes
            if (jsonData.products[aProduct]['id'] == ids[0] && (!ids[1] || jsonData.products[aProduct]['idCombination'] == ids[1])) {
              stayInTheCart = true;
              // update the product customization display (when the product is still in the cart)
              ajaxCart.hideOldProductCustomizations(jsonData.products[aProduct], domIdProduct);
            }
          }
        }
        //remove product if it's no more in the cart
        if (!stayInTheCart) {
          removedProductId = $(this).data('id');
          if (removedProductId != null) {
            firstCut = removedProductId.replace('cart_block_product_', '');
            ids = firstCut.split('_');

            $('dt[data-id="' + removedProductId + '"]').addClass('strike').fadeTo('slow', 0, function() {
              $(this).slideUp('slow', function() {
                $(this).remove();
                // If the cart is now empty, show the 'no product in the cart' message and close detail
                if ($('.cart_block:first dl.products dt').length == 0) {
                  $('.ajax_cart_quantity').html('0');
                  $('#header .cart_block').stop(true, true).slideUp();
                  $('.cart_block_no_products:hidden').slideDown();
                  $('.cart_block dl.products').remove();
                }
              });
            });

            $('dd[data-id="cart_block_combination_of_' + ids[0] + (ids[1] ? '_' + ids[1] : '') + (ids[2] ? '_' + ids[2] : '') + '"]').fadeTo('fast', 0, function() {
              $(this).slideUp('fast', function() {
                $(this).remove();
              });
            });
          }
        }
      });
    }
  },

  /**
   * @param {{ id, idCombination, hasCustomizedDatas, is_gift }} product
   * @param domIdProduct
   */
  hideOldProductCustomizations: function(product, domIdProduct) {
    var $customizationList = $('ul[data-id="customization_' + product['id'] + '_' + product['idCombination'] + '"]');
    if ($customizationList.length > 0) {
      $($customizationList).find('li').each(function() {
        $(this).find('.deleteCustomizableProduct').each(function() {
          var customizationDiv = $(this).data('id');
          var tmp = customizationDiv.replace('deleteCustomizableProduct_', '');
          var ids = tmp.split('_');
          if ((parseInt(product.idCombination) == parseInt(ids[2])) && !ajaxCart.doesCustomizationStillExist(product, ids[0])) {
            $('div[data-id="' + customizationDiv + '"]').parent().fadeTo('slow', 0, function() {
              $(this).slideUp().remove();
            });
          }
        });
      });
    }

    var $removeLinks = $('.deleteCustomizableProduct[data-id="' + domIdProduct + '"]').find('.ajax_cart_block_remove_link');

    // @TODO This is never called
    if (!product.hasCustomizedDatas && !$removeLinks.length) {
      $('div[data-id="' + domIdProduct + '"] span.remove_link').html('<a class="ajax_cart_block_remove_link" rel="nofollow" href="' +
        baseUri + '?controller=cart&amp;delete=1&amp;id_product=' + product['id'] + '&amp;ipa=' + product['idCombination'] +
        '&amp;token=' + static_token + '"><i class="icon icon-times"></i></a>');
    }

    // @TODO This is never called
    if (product.is_gift) {
      $('div[data-id="' + domIdProduct + '"] span.remove_link').html('');
    }
  },

  /**
   * @param {{ customizedDatas }} product
   * @param customizationId
   * @returns {boolean}
   */
  doesCustomizationStillExist: function(product, customizationId) {
    var exists = false;

    $(product.customizedDatas).each(function() {
      if (this.customizationId == customizationId) {
        exists = true;
        // This return does not mean that we found nothing but simply break the loop
        return false;
      }
    });
    return exists;
  },

  /**
   * Refresh display of vouchers (needed for vouchers in % of the total)
   *
   * @param {{ discounts }} jsonData
   */
  refreshVouchers: function(jsonData) {

    var $vouchers      = $('.vouchers');
    var $vouchersTbody = $vouchers.find('tbody');

    if (typeof(jsonData.discounts) == 'undefined' || jsonData.discounts.length == 0) {
      $vouchers.hide();
    } else {
      $vouchersTbody.html('');

      for (var i = 0; i < jsonData.discounts.length; i++) {
        /** @var {{ price_float, code, link, description, name, price, id }} discount */
        var discount = jsonData.discounts[i];

        if (parseFloat(discount.price_float) > 0) {
          var delete_link = '';
          if (discount.code.length) {
            delete_link = '<a class="delete_voucher" href="' + discount.link + '" title="' + delete_txt + '"><i class="icon icon-times"></i></a>';
          }

          $vouchersTbody.append($(
            '<tr class="bloc_cart_voucher" data-id="bloc_cart_voucher_' + discount.id + '">' + ' <td class="quantity">1 x</td>' + ' <td class="name" title="' +
            discount.description + '">' + discount.name + '</td>' + ' <td class="price">-' + discount.price + '</td>' +
            ' <td class="delete">' + delete_link + '</td>' + '</tr>'
          ));
        }
      }
      $vouchers.show();
    }
  },

  /**
   * Update product quantity
   *
   * @param {{ id, idCombination, idAddressDelivery }} product
   * @param quantity
   */
  updateProductQuantity: function(product, quantity) {
    $('dt[data-id=cart_block_product_' + product.id + '_' + (product.idCombination ? product.idCombination : '0') + '_' +
      (product.idAddressDelivery ? product.idAddressDelivery : '0') + '] .quantity').fadeTo('fast', 0, function() {
      $(this).text(quantity);
      $(this).fadeTo('fast', 1, function() {
        $(this).fadeTo('fast', 0, function() {
          $(this).fadeTo('fast', 1, function() {
            $(this).fadeTo('fast', 0, function() {
              $(this).fadeTo('fast', 1);
            });
          });
        });
      });
    });
  },

  //display the products witch are in json data but not already displayed
  displayNewProducts: function(jsonData) {
    //add every new products or update displaying of every updated products

    $(jsonData.products).each(
      /**
       * @param index
       * @param {{ id, idCombination, idAddressDelivery, hasAttributes, attributes, image_cart, name, quantity, link,
       * price_float, priceByLine, is_gift, hasCustomizedDatas }} p - Product object
       */
      function(index, p) {

        //fix ie6 bug (one more item 'undefined' in IE6)
        if (p.id != undefined) {
          //create a container for listing the products and hide the 'no product in the cart' message (only if the cart was empty)

          if ($('.cart_block:first dl.products').length == 0) {
            $('.cart_block_no_products').before('<dl class="products"></dl>').hide();
          }
          //if product is not in the displayed cart, add a new product's line
          var domIdProduct = p.id + '_' + (p.idCombination ? p.idCombination : '0') + '_' + (p.idAddressDelivery ? p.idAddressDelivery : '0');
          var domIdProductAttribute = p.id + '_' + (p.idCombination ? p.idCombination : '0');

          var $cartProductLine = $('dt[data-id="cart_block_product_' + domIdProduct + '"]');
          if ($cartProductLine.length == 0) {

            var productId = parseInt(p.id);

            // var productAttributeId = (p.hasAttributes ? parseInt(p.attributes) : 0);

            var content =  '<dt class="unvisible clearfix" data-id="cart_block_product_' + domIdProduct + '">';
            var name = $.trim($('<span />').html(p.name).text());

            name = (name.length > 12 ? name.substring(0, 10) + '...' : name);

            content += '<a class="cart-images" href="' + p.link + '" title="' + name + '"><img  src="' + p.image_cart + '" alt="' + p.name + '"></a>';

            content += '<div class="cart-info"><div class="product-name">' + '<span class="quantity-formatted"><span class="quantity">' +
              p.quantity + '</span> &times;</span> <a href="' + p.link + '" title="' + p.name + '" class="cart_block_product_name">' + name + '</a></div>';

            if (p.hasAttributes) {
              content += '<div class="product-attributes"><a href="' + p.link + '" title="' + p.name + '">' + p.attributes + '</a></div>';
            }

            if (typeof(freeProductTranslation) != 'undefined') {
              content += '<span class="price">' + (parseFloat(p.price_float) > 0 ? p.priceByLine : freeProductTranslation) + '</span>';
            }

            content += '</div>';

            if (typeof(p.is_gift) == 'undefined' || p.is_gift == 0) {
              content += '<span class="remove_link"><a rel="nofollow" class="ajax_cart_block_remove_link" href="' + baseUri +
                '?controller=cart&amp;delete=1&amp;id_product=' + productId + '&amp;token=' + static_token +
                (p.hasAttributes ? '&amp;ipa=' + parseInt(p.idCombination) : '') + '"><i class="icon icon-times"></i></a></span>';
            } else {
              content += '<span class="remove_link"><i class="icon icon-times"></i></span>';
            }

            content += '</dt>';

            if (p.hasAttributes) {
              content += '<dd data-id="cart_block_combination_of_' + domIdProduct + '" class="unvisible">';
            }

            if (p.hasCustomizedDatas) {
              content += ajaxCart.displayNewCustomizedDatas(p);
            }

            if (p.hasAttributes) {
              content += '</dd>';
            }

            $('.cart_block dl.products').append(content);

          } else {
            //else update the product's line

            if ($.trim($cartProductLine.find('.quantity').html()) != p.quantity || $.trim($cartProductLine.find('.price').html()) != p.priceByLine) {
              // Usual product
              if (!p.is_gift) {
                $cartProductLine.find('.price').text(p.priceByLine);
              } else {
                $cartProductLine.find('.price').html(freeProductTranslation);
              }

              ajaxCart.updateProductQuantity(p, p.quantity);

              // Customized product
              if (p.hasCustomizedDatas) {
                var customizationFormatedDatas = ajaxCart.displayNewCustomizedDatas(p);

                var $customizationList = $('ul[data-id="customization_' + domIdProductAttribute + '"]');
                if (!$customizationList.length) {
                  if (p.hasAttributes) {
                    $('dd[data-id="cart_block_combination_of_' + domIdProduct + '"]').append(customizationFormatedDatas);
                  } else {
                    $('.cart_block dl.products').append(customizationFormatedDatas);
                  }
                } else {
                  $customizationList.html('').append(customizationFormatedDatas);
                }
              }
            }
          }

          $('.cart_block dl.products .unvisible').slideDown().removeClass('unvisible');

          // Remove default product remove button, leave remove buttons on customized rows only
          var $removeLinks = $cartProductLine.find('a.ajax_cart_block_remove_link');
          if (p.hasCustomizedDatas && $removeLinks.length) {
            $removeLinks.remove();
          }
        }
      });
  },

  displayNewCustomizedDatas: function(product) {
    var content = '';
    var productId = parseInt(product.id);
    var productAttributeId = typeof(product.idCombination) == 'undefined' ? 0 : parseInt(product.idCombination);
    var $customizationList = $('ul[data-id="customization_' + productId + '_' + productAttributeId + '"]');
    var hasAlreadyCustomizations = $customizationList.length > 0;

    if (!hasAlreadyCustomizations) {
      if (!product.hasAttributes) {
        content += '<dd data-id="cart_block_combination_of_' + productId + '" class="unvisible">';
      }
      if ($customizationList.val() == undefined) {
        content += '<ul class="cart_block_customizations list-unstyled" data-id="customization_' + productId + '_' + productAttributeId + '">';
      }
    }

    $(product.customizedDatas).each(function() {
      var done = 0;
      var customizationId = parseInt(this.customizationId);

      productAttributeId = typeof(product.idCombination) == 'undefined' ? 0 : parseInt(product.idCombination);

      content += '<li name="customization"><div class="deleteCustomizableProduct" data-id="deleteCustomizableProduct_' +
        customizationId + '_' + productId + '_' + (productAttributeId ?  productAttributeId : '0') +
        '"><a rel="nofollow" class="ajax_cart_block_remove_link" href="' + baseUri + '?controller=cart&amp;delete=1&amp;id_product=' +
        productId + '&amp;ipa=' + productAttributeId + '&amp;id_customization=' + customizationId + '&amp;token=' + static_token +
        '"><i class="icon icon-times"></i></a></div>';

      // Give to the customized product the first textfield value as name
      $(this.datas).each(function() {
        if (this['type'] == CUSTOMIZE_TEXTFIELD) {
          $(this.datas).each(function() {
            if (this['index'] == 0) {
              content += ' ' + this.truncatedValue.replace(/<br \/>/g, ' ');
              done = 1;
              return false;
            }
          });
        }
      });

      // If the customized product did not have any textfield, it will have the customizationId as name
      if (!done) {
        content += customizationIdMessage + customizationId;
      }

      if (!hasAlreadyCustomizations) {
        content += '</li>';
      }
      // Field cleaning
      if (customizationId) {
        $('#uploadable_files').find('li div.customizationUploadBrowse img').remove();
        $('#text_fields').find('input').attr('value', '');
      }
    });

    if (!hasAlreadyCustomizations) {
      content += '</ul>';
      if (!product.hasAttributes) {
        content += '</dd>';
      }
    }

    return content;
  },

  updateLayer: function(product) {

    var $attributes = $('#layer_cart_product_attributes');

    $('#layer_cart_product_title').text(product.name);
    $attributes.text('');
    if (product.hasAttributes && product.hasAttributes == true) {
      $attributes.html(product.attributes);
    }

    $('#layer_cart_product_price').text(product.price);
    $('#layer_cart_product_quantity').text(product.quantity);
    $('.layer_cart_img').html('<img class="layer_cart_img img-responsive" src="' + product.image + '" alt="' + product.name + '" title="' + product.name + '" />');

    var n = parseInt($(window).scrollTop()) + 'px';

    $('.layer_cart_overlay').css({'width': '100%', 'height': '100%'}).show();
    $('#layer_cart').css({'top': n}).fadeIn('fast');
  },

  // General update of the cart display
  updateCart: function(jsonData) {
    //user errors display
    if (jsonData.hasError) {
      PrestaShop.showError(jsonData.errors);
    } else {
      ajaxCart.updateCartEverywhere(jsonData);
      ajaxCart.hideOldProducts(jsonData);
      ajaxCart.displayNewProducts(jsonData);
      ajaxCart.refreshVouchers(jsonData);
    }
  },

  /**
   * Update general cart information everywhere in the page
   *
   * @param {{ productTotal, shippingCostFloat, shippingCost, free_ship, isVirtualCart, taxCost, wrappingCost,
    *          total, total_price_wt, freeShipping, freeShippingFloat, nbTotalProducts }} cart
   */
  updateCartEverywhere: function(cart) {

    var $total           = $('.ajax_cart_total');
    var $shippingCost    = $('.ajax_cart_shipping_cost');
    var $shippingCostRow = $shippingCost.closest('.cart-prices-line');
    var $freeShipping    = $('.freeshipping');
    var $quantity        = $('.ajax_cart_quantity');
    var $productTxt      = $('.ajax_cart_product_txt');
    var $productTxtS     = $('.ajax_cart_product_txt_s');
    var $noProduct       = $('.ajax_cart_no_product');

    $total.text($.trim(cart.productTotal));

    if (typeof hasDeliveryAddress == 'undefined') {
      window.hasDeliveryAddress = false;
    }

    if (parseFloat(cart.shippingCostFloat) > 0) {
      $shippingCost.text(cart.shippingCost);
      $shippingCostRow.show();
    } else if ((hasDeliveryAddress || typeof(orderProcess) !== 'undefined' && orderProcess == 'order-opc') && typeof(freeShippingTranslation) != 'undefined') {
      $shippingCost.html(freeShippingTranslation);
    } else if ((typeof toBeDetermined !== 'undefined') && !hasDeliveryAddress) {
      $shippingCost.html(toBeDetermined);
    }

    if (!cart.shippingCostFloat && !cart.free_ship) {
      $shippingCostRow.hide();
    } else if (hasDeliveryAddress && !cart.isVirtualCart) {
      $shippingCostRow.show();
    }

    $('.ajax_cart_tax_cost').text(cart.taxCost);
    $('.ajax_block_wrapping_cost').text(cart.wrappingCost);
    $('.ajax_block_cart_total').text(cart.total);
    $('.ajax_block_products_total').text(cart.productTotal);
    $('.ajax_total_price_wt').text(cart.total_price_wt);

    if (parseFloat(cart.freeShippingFloat) > 0) {
      $('.ajax_cart_free_shipping').html(cart.freeShipping);
      $freeShipping.fadeIn(0);
    } else if (parseFloat(cart.freeShippingFloat) == 0) {
      $freeShipping.fadeOut(0);
    }

    this.nb_total_products = cart.nbTotalProducts;

    if (parseInt(cart.nbTotalProducts) > 0) {

      var multipleProducts = parseInt(cart.nbTotalProducts) > 1;

      $noProduct.hide();
      $quantity.text(cart.nbTotalProducts).fadeIn();
      $total.fadeIn();
      $productTxt.toggle(!multipleProducts);
      $productTxtS.toggle(multipleProducts);

    } else {
      $total.hide();
      $quantity.hide();
      $productTxt.hide();
      $productTxtS.hide();
      $noProduct.show();
    }
  }
};

function HoverWatcher(selector) {
  this.hovering = false;
  var self = this;

  this.isHoveringOver = function() {
    return self.hovering;
  };

  $(selector).on({
    mouseenter: function() {
      self.hovering = true;
    },
    mouseleave: function() {
      self.hovering = false;
    }
  });
}
