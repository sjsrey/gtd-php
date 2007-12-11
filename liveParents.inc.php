<table summary='parents selected' class='datatable' id='parenttable' style='display:inline'>
    <tbody id='parentlist'>
        <tr>
            <td><a class='add' href='javascript:gtd_search();' title='Click here to select parent(s)'>+</a></td>
            <td><a href='javascript:gtd_search();' title='Click here to select parent(s)'>Select parent(s)</a></td>
            <td>&nbsp;</td>
        </tr><?php
            if (is_array($parents) && count($parents)) foreach ($parents as $parent) {
                $ptype=($parent['isSomeday']=='y')?'s':$parent['ptype'];
                echo "<tr id='parentrow{$parent['parentId']}'>"
                    ,"<td><a href='javascript:removeParent(\"{$parent['parentId']}\")'
                            title='remove as parent' class='remove'>X</a></td>"
                    ,"<td><a href='itemReport.php?itemId={$parent['parentId']}' title='view parent'>"
                    ,makeclean($parent['ptitle']),"</a></td>"
                    ,"<td>",getTypes($ptype)
                    ,"<input type='hidden' name='parentId[]' value='{$parent['parentId']}' /></td>"
                    ,"</tr>\n";
                }
    ?></tbody>
</table>
