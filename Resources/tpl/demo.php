{{include_file='header'}}
<ul>
{{foreach $tpl.data.data as $k=>$vv}}
<li>{{$foreach.vv.uid}}:<span style="color:#f00">{{$foreach.vv.username}}</span></li>
{{/foreach}}
</ul>

{{if (count($tpl.data)>1 and $tpl.title)}}

{{count($tpl.data)}}

<P>

</P>

{{endif}}
{{$view.session.requestUri}}
{{include_file='footer'}}

	