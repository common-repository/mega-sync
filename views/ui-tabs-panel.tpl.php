<?php if(!defined( 'ABSPATH')) exit; ?>
<div class="cf7hsfi">
	<h2>Hubspot Forms Integration Settings</h2>
	<fieldset>
	  <legend>
	  In order for your Contact Form 7 form submissions to work with HubSpot Form, you must fill the HubSpot form credentials and required fields below.
	  </legend>
		<table class="form-table">
		  <tbody>
		    <tr>
		      <th scope="row">Activate</th>
		      <td>
		        <input type="checkbox" name="cf7hsfi_enabled" id="cf7hsfi_enabled" value="1"{enabled}> 
		        <label for="cf7hsfi_enabled">Enable</label>
		        <p class="description">Activate Form Integration</p>
		      </td>
		    </tr>
		    <tr>
		      <th scope="row">Portal ID</th>
		      <td>
		        <input type="text" name="cf7hsfi_portal_id" class="large-text code" value="{portal_id}" placeholder="e.g. 2159505">
		        <p class="description">HubSpot Portal ID <b>(Required)</b></p>
		      </td>
		    </tr>
		    <tr>
		      <th scope="row">Form ID</th>
		      <td>
		        <input type="text" name="cf7hsfi_form_id" class="large-text code" value="{form_id}" placeholder="e.g. aq2ab21w-e60d-4v63-801b-3d330h817445">
		        <p class="description">HubSpot Form ID <b>(Required)</b></p>
		      </td>
		    </tr>
		    <tr>
		      <th scope="row">Page URL</th>
		      <td>
		        <input type="text" name="cf7hsfi_form_page_url" class="large-text code" value="{form_page_url}" placeholder="e.g. {site_url}/contact-form-page/">
		        <p class="description">The Form Page URL <b>(Optional)</b></p>
		      </td>
		    </tr>
		    <tr>
		      <th scope="row">Page Name</th>
		      <td>
		        <input type="text" name="cf7hsfi_form_page_name" class="large-text code" value="{form_page_name}" placeholder="e.g. Contact Form Page">
		        <p class="description">The Form Page Name <b>(Optional)</b></p>
		      </td>
		    </tr>
		    <tr>
		      <th scope="row">Form Fields <b>(Required)</b></th>
		      <td class="valign-top">
			      <div class="cf7hsfi_form_field_names_wrap">
			      	<span class="cf7hsfi_form_fields"></span>
			        {form_fields_html}
			      </div>
			      <p class="cf7_field_names"></p>
			      <p class="description info">
			      Map the form field names and values accordingly.<br>
			      Use the contact form 7 field against the hubspot form field name.<br>
			      e.g. <strong>HubSpot Form Field Name <i class="icon-arrow-right" style="line-height: 18px;"></i> Contact Form 7 Form Field Name</strong> 
			      </p>
		      </td>
		    </tr>
		  </tbody>
		</table>
	</fieldset>	
</div>
