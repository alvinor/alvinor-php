{{include_file='header'}}
<style>
p{
	color:#0f0;
	font:24px;
}

</style>
<?php echo  date("Y-m-d H:i:s");?>
<br/>
fsadfasdfsadfsadfsd
{{foreach $tpl.jlaaa as $k=>$v}}
<p>{{$foreach.v}}</p>
{{/foreach}}
{{include_file='footer'}}