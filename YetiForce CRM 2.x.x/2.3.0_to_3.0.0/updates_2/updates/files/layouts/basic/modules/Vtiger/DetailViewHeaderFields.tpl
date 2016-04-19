{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
	<div class="col-xs-12 col-sm-12 col-md-4 detailViewHeaderFields">
		{if $FIELDS_HEADER}
			{foreach from=$FIELDS_HEADER key=LABEL item=VALUE}
				{if !empty($VALUE['value'])}
					<div class="col-md-12 marginTB3 paddingLRZero">
						<div class="row col-lg-9 col-md-10 pull-right paddingLRZero detailViewHeaderFieldsContent">
							<div class="btn {$VALUE['class']} btn-xs col-md-12">
								<div class="detailViewHeaderFieldsName">
									{vtranslate($LABEL, $MODULE_NAME)} 
								</div>
								<div class="detailViewHeaderFieldsValue">
									<span class="badge">
										{$VALUE['value']}
									</span>
								</div>
							</div>
						</div>
					</div>
				{/if}
			{/foreach}
		{/if}
	</div>
{/strip}
