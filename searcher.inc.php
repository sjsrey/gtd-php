<div id='searcher'>
    <div id='donebox'>
        <a href='javascript:gtd_closesearch();' id='closesearch' title='click, or press escape, to close'>X</a>
    </div>
    <form method='get' action='javascript:gtd_closesearch();'>
        <p>
            <label for="searcherneedle">Search for</label>
            <input type='text' name='searcherneedle' id='searcherneedle' onkeyup='javascript:gtd_refinesearch(this);' />
            in the titles of
        <br /><?php
            foreach ($allowedSearchTypes as $key=>$value)
                echo "&nbsp;&nbsp;<label for='radio$key'>$value</label>\n"
                    ,"<input type='radio' name='qtype' class='notfirst' value='$key' id='radio$key' "
                    ," onclick='javascript:gtd_refinesearch(this);' "
                        ,($key===$values['ptype'])?' checked="checked" ':''
                    ," />&nbsp;\n";
            ?><input type='hidden' name='returntype' value='table' />
        </p>
        <?php /*
        <table id='searchresults' summary='search results' class='datatable'>
            <tbody>
                <tr><td>&nbsp;</td></tr>
            </tbody>
        </table>*/ ?>
        <div id='searchresults'>&nbsp;</div>
    </form>
</div>
