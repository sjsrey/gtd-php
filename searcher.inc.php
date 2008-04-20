<div id='searcher'>
    <div id='donebox'>
        <a href='javascript:GTD.parentselect.close();' id='closesearch' title='click, or press escape, to close'>X</a>
    </div>
    <form method='get' action='#' onsubmit='return GTD.parentselect.close();'>
        <p>
            <label for="searcherneedle">Search for</label>
            <input type='text' name='searcherneedle' id='searcherneedle' onkeyup='return GTD.parentselect.refinesearch(this);' />
            in the titles of
        <br /><?php
            foreach ($allowedSearchTypes as $key=>$value)
                echo "&nbsp;&nbsp;<label for='radio$key'>$value</label>\n"
                    ,"<input type='radio' name='qtype' class='notfirst' value='$key' id='radio$key' "
                    ," onclick='return GTD.parentselect.refinesearch(this);' "
                        ,($key===$values['ptype'])?' checked="checked" ':''
                    ," />&nbsp;\n";
            ?><input type='hidden' name='returntype' value='table' />
        </p>
        <div id='searchresults'>&nbsp;</div>
    </form>
</div>
