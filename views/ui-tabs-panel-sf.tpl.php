<?php if(!defined( 'ABSPATH')) exit; ?>
<div class="cf7sffi">
	<h2>Salesforce Forms Integration Settings</h2>
	<fieldset>
	  <legend>
	  In order for your Contact Form 7 form submissions to work with Salesforce Form, you must fill the Salesforce form credentials and required fields below.
	  </legend>
		<table class="form-table">
		  <tbody>
		    <tr>
		      <th scope="row">Activate</th>
		      <td>
		        <input type="checkbox" name="cf7sffi_enabled" id="cf7sffi_enabled" value="1"{enabled}> 
		        <label for="cf7sffi_enabled">Enable</label>
		        <p class="description">Activate Form Integration</p>
		      </td>
		    </tr>
			<tr>
		      <th scope="row">Salesforce URL</th>
		      <td>
		        <input type="text" name="cf7sffi_url" class="large-text code" value="{sf_url}" placeholder="e.g. https://www.salesforce.com/servlet/servlet.WebToLead?encoding=UTF-8">
		        <p class="description">Salesforce URL <b>(Required)</b></p>
		      </td>
		    </tr>
		    <tr>
		      <th scope="row">Salesforce ID</th>
		      <td>
		        <input type="text" name="cf7sffi_oid" class="large-text code" value="{form_oid}" placeholder="e.g. 00XX000000XXXXX">
		        <p class="description">Salesforce ID <b>(Required)</b></p>
		      </td>
		    </tr>
		    <tr>
		      <th scope="row">Form Fields <b>(Required)</b></th>
		      <td class="valign-top">
			      <div class="cf7sffi_form_field_names_wrap">
			      	<span class="cf7sffi_form_fields"></span>
			        {form_fields_html}
			      </div>
			      <p class="cf7_field_names"></p>
			      <p class="description info">
			      Map the form field names and values accordingly.<br>
			      Use the contact form 7 field against the Salesforce form field name.<br>
			      e.g. <strong>Salesforce Form Field Name <i class="icon-arrow-right" style="line-height: 18px;"></i> Contact Form 7 Form Field Name</strong> 
			      </p>
		      </td>
		    </tr>
		  </tbody>
		</table>
	</fieldset>	
</div>
