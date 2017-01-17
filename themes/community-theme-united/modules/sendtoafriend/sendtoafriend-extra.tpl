<li class="sendtofriend">
  <a id="send_friend_button" href="#send_friend_form">
    <i class="icon icon-fw icon-envelope-o"></i> {l s='Send to a friend' mod='sendtoafriend'}
  </a>

  <div style="display: none;">
    <div id="send_friend_form">
      <h2  class="page-subheading">{l s='Send to a friend' mod='sendtoafriend'}</h2>
      <div class="row">

        <div class="product clearfix col-xs-12 col-sm-6">
          <div class="thumbnail">
            <img class="img-responsive" src="{$link->getImageLink($stf_product->link_rewrite, $stf_product_cover, 'home_default')|escape:'html':'UTF-8'}" height="{$homeSize.height}" width="{$homeSize.width}" alt="{$stf_product->name|escape:'html':'UTF-8'}" />
          </div>
          <h5><b>{$stf_product->name}</b></h5>
          <p>{$stf_product->description_short}</p>
        </div>

        <div class="send_friend_form_content col-xs-12 col-sm-6" id="send_friend_form_content">
          <div id="send_friend_form_error" class="alert alert-danger" style="display: none;"></div>
          <h5>{l s='Recipient' mod='sendtoafriend'}</h5>
          <div class="form-group">
            <label for="friend_name">
              {l s='Name of your friend' mod='sendtoafriend'} <sup class="required">*</sup> :
            </label>
            <input id="friend_name" class="form-control" name="friend_name" type="text" value="" required/>
          </div>
          <div class="form-group">
            <label for="friend_email">
              {l s='E-mail address of your friend' mod='sendtoafriend'} <sup class="required">*</sup> :
            </label>
            <input id="friend_email" class="form-control" name="friend_email" type="email" value="" required/>
          </div>
          <div class="form-group">
            <div class="help-block">
              <sup class="required">*</sup> {l s='Required fields' mod='sendtoafriend'}
            </div>
          </div>
          <button id="sendEmail" class="btn btn-primary" name="sendEmail" type="submit">{l s='Send' mod='sendtoafriend'}</button>
          <a class="closefb btn btn-link" href="#">{l s='Cancel' mod='sendtoafriend'}</a>
        </div>
      </div>
    </div>
  </div>

</li>

{addJsDef stf_secure_key=$stf_secure_key}
{addJsDefL name=stf_msg_success}{l s='Your e-mail has been sent successfully' mod='sendtoafriend' js=1}{/addJsDefL}
{addJsDefL name=stf_msg_error}{l s='Your e-mail could not be sent. Please check the e-mail address and try again.' mod='sendtoafriend' js=1}{/addJsDefL}
{addJsDefL name=stf_msg_title}{l s='Send to a friend' mod='sendtoafriend' js=1}{/addJsDefL}
{addJsDefL name=stf_msg_required}{l s='You did not fill required fields' mod='sendtoafriend' js=1}{/addJsDefL}
