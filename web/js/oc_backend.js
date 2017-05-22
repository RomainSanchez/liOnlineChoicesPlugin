
var header_cell = '<th class="sf_admin_text sf_admin_list_th_id ui-state-default ui-th-column"></th>';
var link_day = '<a href="#" class="fg-button ui-widget ui-state-default ui-corner-all"></a>';
var choices = ['none', 'one', 'two', 'three'];

function gaugeChange(cell, value) {
  var gauge = $('.plan_gauges th').eq(cell.index()).find('.gauge');
  var part = parseInt(gauge.attr('data-part')) + value;
  gauge.attr('data-part', part);
}

function gaugeInc(cell) {
  gaugeChange(cell, 1);
}

function gaugeDec(cell) {
  gaugeChange(cell, -1);
}

function validate() {
  saveSnapshot('valid');
}

function loadSnapshot(url) {
  $.ajax({
    url: url,
    data: {},
    method: 'get',
    success: function(data){      
      $('.plan_body').html('');
      addPros(data);
      refreshGauges();
      $('#transition .close').click();
    },
    error: function(data){
      console.log(data);
    }
  });
}

function saveSnapshot(type = 'save') {
  var snapshot = [];
  
  $('.plan_body tr').each(function() {
    var contact = new Object();
    contact.id = $(this).find('td:first-child').attr('data-id');
    contact.name = $(this).find('td:first-child').text();
    contact.manifestations = [];
    $(this).find('td').filter('.none, .algo, .human').each(function() {
      var manifestation = new Object();
      manifestation.id = $('.plan_events th').eq($(this).index()-1).attr('data-id');
      manifestation.rank = $(this).text();
      var accepted = $(this).attr('class').match(/none|algo|human/g);
      if ( accepted.length > 0 )
        manifestation.accepted = accepted[0];
        
      contact.manifestations.push(manifestation);
    });
    snapshot.push(contact);
  });
  
  $.post($('.save_snapshot_popup').attr('data-url'), 
    {
      content: JSON.stringify(snapshot),
      name: $('#snapshot_name').val(),
      day: $('.plan_day th').eq(1).attr('data-date'),
      purpose: type,
      _csrf_token: $('#_csrf_token').val()
    }, 
    function(data) {
      console.log(data);
      $('#transition .close').click();
  });
}

function refreshGauges() {
  
  $('.plan_gauges th').each(function() {
    var gauge = $(this).find('div > .gauge');
    gauge.find('.text').text(gauge.attr('data-part') + ' / ' + gauge.attr('data-max'));
    var part = parseInt(gauge.attr('data-part'));
    var max = parseInt(gauge.attr('data-max'));
    var resa = part / max * 100;
    
    if ( part > max ) {
      gauge.find('.resa').removeClass('resa').addClass('over');
      resa = (part - max) / max * 100;
      gauge.find('.over').css('width', resa + '%');
      gauge.removeClass('free').addClass('resa');
    } else {
      gauge.find('.over').removeClass('over').addClass('resa');
      gauge.find('.resa').css('width', resa + '%');
      gauge.removeClass('resa').addClass('free');
    }
  });
}

function loadDay(data, length) {
  var header_gauges = $(header_cell)
    .attr('rowspan', 3)
    .appendTo('.plan_day');
    
  var header_day = $(header_cell)
    .attr('colspan', length)
    .attr('data-date', data.current.date)
    .appendTo('.plan_day');
       
  var previous = $('<div class="plan_previous floatleft"></div>').appendTo(header_day);
  var next = $('<div class="plan_next floatright"></div>').appendTo(header_day);

  if ( data.previous.day ) {
    $(link_day)
      .text(data.previous.day)
      .appendTo(previous)
      .attr('href', '?date='+data.previous.date);
  }
  if ( data.next.day ) {
    $(link_day)
      .text(data.next.day)
      .appendTo(next)
      .attr('href', '?date='+data.next.date);
  }

  $('<span></span>').appendTo(header_day).text(data.current.day);
}

function loadHours(data) {

  var header_gauges = $(header_cell)
    .appendTo('.plan_gauges'); 
  $('<span></span>').appendTo(header_gauges).text('Participants');

  var i = 0;

  $.each(data, function(key, manifestation) {
    var header_hours = $(header_cell)
      .attr('colspan', manifestation.events.length)
      .attr('data-grp-id', manifestation.time_id)
      .attr('data-min', i)
      .appendTo('.plan_hours'); 
    $('<span></span>').appendTo(header_hours).text(manifestation.range);
    
    $.each(manifestation.events, function(key, event) {
      i++;
      var header_events = $(header_cell)
        .attr('data-id', event.id)
        .attr('data-grp-id', manifestation.time_id)
        .appendTo('.plan_events'); 
      $('<span></span>').appendTo(header_events).text(event.name);
      
      var header_gauges = $(header_cell)
        .appendTo('.plan_gauges'); 
      $('.raw').clone()
        .removeClass('raw')
        .appendTo(header_gauges)
        .find('.gauge')
        .attr('data-part', event.gauge.part)
        .attr('data-max', event.gauge.value);
    });
    
    header_hours.attr('data-max', i);
  });
}

function addPros(data) {
  $('.plan_gauges th .gauge').attr('data-part', 0);
  
  $.each(data, function(i, pro) {
    var row_pro = $('<tr class="sf_admin_row ui-widget-content"></tr>')
      .appendTo('.plan_body');
    if ( i%2 ) {
      row_pro.addClass('odd');
    }
    
    $('<td nowrap></td>')
      .attr('data-id', pro.id)
      .appendTo(row_pro).text(pro.name);

    $('.plan_events th').each(function() {
      var manif_cell = $('<td></td>')
        .addClass('cell_choices')
        .appendTo(row_pro)
        .click(function() {
          var group_id = $('.plan_events th').eq($(this).index()-1).attr('data-grp-id');
          var group = $('.plan_hours th[data-grp-id='+group_id+']');
          var min = parseInt(group.attr('data-min'))+2;
          var max = parseInt(group.attr('data-max'))+1;
          var previous_selected = $(this).closest('tr').find('td:nth-child(n+'+min+'):nth-child(-n+'+max+')').filter('.algo, .human');
          
          if ( previous_selected.length > 0 ) {
            previous_selected.removeClass('none algo human');
            gaugeDec(previous_selected);
          }
          
          $(this).removeClass('none algo human');
          $(this).addClass('human');
          gaugeInc($(this));
          refreshGauges();
        });
      var m_id = $(this).attr('data-id');
      var g_id = $(this).index() + 1;

      $.each(pro.manifestations, function(i, manif) {
        if ( manif.id == m_id) {
          manif_cell.addClass(manif.accepted);

          if ( manif.accepted != 'none' ) {
            var gauge = $('.plan_gauges th').eq(g_id).find('.gauge');
            gauge.attr('data-part', parseInt(gauge.attr('data-part')) + 1);
          }
          
          if ( manif.rank > 0 ) {
            $('<span></span>').text(manif.rank).appendTo(
              $('<div class="round"></div>')
                .addClass(choices[manif.rank])
                .appendTo(manif_cell)
            );
          }
          return false;
        }
      });
    });
  });

  var header_pos = 0;
  $('.plan_hours th').addClass('time_slot_left').each(function() {
    $('.plan_events th').eq(header_pos).addClass('time_slot_left');
    $('.plan_gauges th').eq(header_pos+1).addClass('time_slot_left');
    $('.plan_body td:nth-child('+(header_pos+2)+')').addClass('time_slot_left');
    header_pos += parseInt($(this).attr('colspan'));
  });
}

function loadPros(length, date) {
  $.ajax({
    url: $('.plan_body').attr('data-url') + (date !== undefined ? '?date=' + date : ''),
    data: {},
    method: 'get',
    success: function(data){
      addPros(data);
    },
    error: function(data){
      console.log(data);
    }
  });
}

function loadHeaders(date) {
  $.ajax({
    url: $('.plan_header').attr('data-url') + (date !== undefined ? '?date=' + date : ''),
    data: {},
    method: 'get',
    success: function(data){      
      $('.plan_body').html('');
      $('.plan_header tr').html('');
      loadDay(data, data.length);
      loadHours(data.manifestations);
      loadPros(data.length, date);
      refreshGauges();
    },
    error: function(data){
      console.log(data);
    }
  });
}

$(document).ready(function(){

  loadHeaders($('.plan_day').attr('data-day'));
  
  $('.validate').click(function() {
    validate();
  });
  
  $('.save_popup').click(function() {
    $('.snapshot_save').show();
  });
  
  $('.load_popup').click(function() {
    $('.snapshot_load').show();
  });
  
  $('.snapshot').click(function(event) {
    event.preventDefault();
    $('#transition').fadeIn('medium');
    loadSnapshot($(this).attr('href'));
    $('.popup_close').click();
  })

  $('.popup_close').click(function() {
    $('.snapshot_load').hide();
  });
  
  $('.save_snapshot_popup').click(function() {
    $('#transition').fadeIn('medium');
    $('.popup_close').click();
    saveSnapshot();
  });
  
  $('.popup_close').click(function() {
    $('.snapshot_save').hide();
  });
  
});