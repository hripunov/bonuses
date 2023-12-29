<div class="formbox" >
                        <form method="POST" action="{urlmake}" enctype="multipart/form-data" class="crud-form">
            <input type="submit" value="" style="display:none">
            <div class="notabs">
                                                                                                            
                                                                                            
                                                                                            
                                                                                            
                                                                                            
                                                                                            
                                                                                            
                                                                                            
                                                    
                                    <table class="otable">
                                                                                                                    
                                <tr>
                                    <td class="otitle">{$elem.__user_id->getTitle()}&nbsp;&nbsp;{if $elem.__user_id->getHint() != ''}<a class="help-icon" title="{$elem.__user_id->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__user_id->getRenderTemplate() field=$elem.__user_id}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__amount->getTitle()}&nbsp;&nbsp;{if $elem.__amount->getHint() != ''}<a class="help-icon" title="{$elem.__amount->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__amount->getRenderTemplate() field=$elem.__amount}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__reason->getTitle()}&nbsp;&nbsp;{if $elem.__reason->getHint() != ''}<a class="help-icon" title="{$elem.__reason->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__reason->getRenderTemplate() field=$elem.__reason}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__dateof->getTitle()}&nbsp;&nbsp;{if $elem.__dateof->getHint() != ''}<a class="help-icon" title="{$elem.__dateof->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__dateof->getRenderTemplate() field=$elem.__dateof}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__extra->getTitle()}&nbsp;&nbsp;{if $elem.__extra->getHint() != ''}<a class="help-icon" title="{$elem.__extra->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__extra->getRenderTemplate() field=$elem.__extra}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__writeoff->getTitle()}&nbsp;&nbsp;{if $elem.__writeoff->getHint() != ''}<a class="help-icon" title="{$elem.__writeoff->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__writeoff->getRenderTemplate() field=$elem.__writeoff}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__notify1->getTitle()}&nbsp;&nbsp;{if $elem.__notify1->getHint() != ''}<a class="help-icon" title="{$elem.__notify1->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__notify1->getRenderTemplate() field=$elem.__notify1}</td>
                                </tr>
                                                                                                                            
                                <tr>
                                    <td class="otitle">{$elem.__notify2->getTitle()}&nbsp;&nbsp;{if $elem.__notify2->getHint() != ''}<a class="help-icon" title="{$elem.__notify2->getHint()|escape}">?</a>{/if}
                                    </td>
                                    <td>{include file=$elem.__notify2->getRenderTemplate() field=$elem.__notify2}</td>
                                </tr>
                                                                                                        </table>
                            </div>
        </form>
    </div>