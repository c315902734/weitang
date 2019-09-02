<?php


class CommonService
{
    public static function parse_editor_info($info)
    {
        return "<style>
    html { margin: 0; padding: 0; }
    body { margin: 0; padding: 5px; }
    body, td { font: 12px/1.5 \"sans serif\", tahoma, verdana, helvetica; }
    body, p, div { word-wrap: break-word; }
    p { margin: 5px 0; }
    table { border-collapse: collapse; }
    img { border: 0; }
    noscript { display: none; }
    table.ke-zeroborder td { border: 1px dotted #AAA; }
img{width: 100%;}
</style>" . $info;
    }
}
