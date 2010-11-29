{if isset($form_settings)}
        <div style="color: red;">{$form_settings->showErrors()}</div>

        {$form_settings->getHeaders()}

        <p style="text-align: right;">
                {$form_settings->getButtons()}
        </p>
        {$form_settings->showWidgets('<div class="pageoverflow">
                <div class="pagetext">%LABEL%:</div>
                <div class="pageinput">%INPUT%<br /><em>%TIPS%</em></div>
                <div class="pageinput" style="color: red;">%ERRORS%</div>
        </div>')}
        {$form_settings->renderFieldsets('<div class="pageoverflow">
                <div class="pagetext">%LABEL%:</div>
                <div class="pageinput">%INPUT%<br /><em>%TIPS%</em></div>
                <div class="pageinput" style="color: red;">%ERRORS%</div>
        </div>')}
        <p style="text-align: right; margin-top: 15px;">
                {$form_settings->getButtons()}
        </p>

        {$form_settings->getFooters()}
{/if}
