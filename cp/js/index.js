$(function(){
  $('.full_datetime').datetimepicker({
        //language:  'fr',
  format: 'yyyy-mm-dd hh:ii',
        weekStart: 7,
        todayBtn:  1,
                autoclose: 1,
                todayHighlight: 1,
                startView: 2,
                forceParse: 0,
       // showMeridian: 1
    });//end of dateTimePicker
$('.dateBirth').datetimepicker({
  format: 'yyyy-mm-dd hh:ii:ss',
  weekStart: 7,
  todayBtn: 1,
  autoclose: 1,
  todayHighlight: 1,
  startView: 4,
  forceParse: 0
});

$('.startDate').datetimepicker({
  title: 'Start Date',
  format: 'yyyy-mm-dd hh:ii:ss',
  startDate: '-1d',
  weekStart: 7,
  todayBtn: 1,
  autoclose: 1,
  todayHighlight: 1,
  startView: 4,
});

$('.stopDate').datetimepicker({
  title: 'Stop Date',
  format: 'yyyy-mm-dd hh:ii:ss',
  startDate: '-1d',
  weekStart: 7,
  todayBtn: 1,
  autoclose: 1,
  todayHighlight: 1,
  startView: 4,
});

$('.day').datetimepicker({
        //language:  'fr',
  format: 'yyyy-mm-dd',
  weekStart: 7,
  todayBtn:  0,
  autoclose: 1,
  todayHighlight: 0,
  startView: 2,
  minView: 2,
  forceParse: 0,
    });

});