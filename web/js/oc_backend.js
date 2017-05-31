if ( liOC === undefined ) {
    var liOC = {};
}

liOC.createHeaderCell = function() {
  return $('.sf_admin_list table tfoot .ui-th-column').clone();
}
liOC.createLinkDay = function() {
  return $('.sf_admin_list table tfoot a').clone();
}

liOC.choices = ['none', 'one', 'two', 'three'];

// init
$(document).ready(function(){
  
  // the content
  liOC.loadHeaders($('.plan_day').attr('data-day'));
  
  $('.validate').click(function() {
    $('#transition').fadeIn('medium');
    liOC.validate($(this).attr('data-url'));
  });
  
  $('.save_popup').click(function() {
    $('.snapshot_save').show();
  });
  
  $('.load_popup').click(function() {
    $('#transition').fadeIn('medium');
    $.ajax({
      url: $(this).attr('data-url'),
      data: {},
      method: 'get',
      success: function(data){
        $('.list_snapshots').html(data);
        $('#transition .close').click();
        $('.snapshot_load').show();
      },
      error: function(data){
        console.log(data);
      }
    });
  });
  
  $('.snapshot').click(function(event) {
    event.preventDefault();
    $('#transition').fadeIn('medium');
    liOC.loadSnapshot($(this).prop('href'));
    $('.popup_close').click();
  })

  $('.popup_close').click(function() {
    $('.snapshot_load').hide();
  });
  
  $('.save_snapshot_popup').click(function() {
    $('#transition').fadeIn('medium');
    $('.popup_close').click();
    liOC.saveSnapshot();
  });
  
  $('.popup_close').click(function() {
    $('.snapshot_save').hide();
  });
  
});

// horizontal+vertical
liOC.fixTableScrollBoth = function(table) {
  var cloneTopLeft = $('<table></table>').addClass('thead-th-clone');
  table.find('thead tr:first th:first-child, thead tr:last th:first-child').each(function(){
    var width = $(this).width();
    var height = $(this).height();
    $(this).width(width).height(height);
  });
  cloneTopLeft.append(table.find('thead').clone()).find('thead')
    .find('th:not(:first-child), tr:not(:first):not(:last)').remove();
  cloneTopLeft.find('thead th[rowspan]').prop('rowspan',1);
  table.find('thead th').width('auto').height('auto');
  var top = table.position().top;
  
  return cloneTopLeft;
}

// horizontal
liOC.fixTableScrollHorizontal = function(table) {
  var cloneLeft = $('<table></table>').addClass('th-clone');
  var width = table.find('tbody th:first').width();
  var height = table.find('tbody th:first').height();
  cloneLeft.append(table.find('tbody').clone()).find('tbody').removeClass('plan_body').find('td, input.rank').remove();

  cloneLeft.find('tbody th').width(width).height(height);
  table.find('tbody th').width('auto').height('auto');
  
  cloneLeft.find('tbody tr > th').click(function(e){
    console.error('data-id', $(this).attr('data-id'));
    liOC.selectPro(e, $('.sf_admin_list table.real tbody tr > th[data-id="'+$(this).attr('data-id')+'"]'));
  });
  
  return cloneLeft;
}

// vertical
liOC.fixTableScrollVertical = function(table){
  var thead = table.find('thead');
  thead.find('td, th').each(function(){
    $(this).width($(this).width());
  });
  cloneTop = $('<table></table>').append(thead.clone()).addClass('thead-clone');
  cloneTop.width(table.width());
  thead.find('td, th').width('auto');
  
  return cloneTop;
}

liOC.fixTableReset = function() {
  $('.sf_admin_list').find('.th-clone, .thead-clone, .thead-th-clone').remove();
}

liOC.fixTableScroll = function() {
  liOC.fixTableReset();
  var table = $('.sf_admin_list table');
  var thead = table.find('thead');
    
  // vertical & vertical+horizontal
  var clones = {
    left: liOC.fixTableScrollHorizontal(table),
    top:  liOC.fixTableScrollVertical(table),
    both: liOC.fixTableScrollBoth(table)
  }
  
  $.each(clones, function(i, clone){
    clone.hide().insertBefore(table);
  });
  
  // both
  $(window).scroll(function(){
    // top
    if ( thead.position().top-$(window).scrollTop()-$('#menu').position().top-$('#menu').height() < 0 ) {
        clones['both'].show();
        clones['top'].show();
    }
    else {
        clones['both'].hide();
        clones['top'].hide();
    }
    clones['top'].css('margin-left', -$(window).scrollLeft());
    
    // left
    if ( $(window).scrollLeft() > table.find('tbody th:first').position().left ) {
        clones['left'].show();
        clones['both'].show();
    }
    else {
        clones['left'].hide();
        clones['both'].hide();
    }
    clones['left'].css('margin-top', -$(window).scrollTop());
    clones['both'].css('top', $(window).scrollTop() > 104 ? 50 : 154-$(window).scrollTop());
  }).scroll();
  
  var to;
  $(window).off('resize').resize(function(){
    if ( to !== undefined ) {
        clearTimeout(to);
    }
    to = setTimeout(function(){
        liOC.fixTableScroll();
    },500);
  });
}

liOC.blockContextMenu = function(){
    $('.sf_admin_list table').contextmenu(function(e){ e.preventDefault(); return false; });
}

liOC.gaugeChange = function(cell, value) {
  $('.plan_gauges').each(function(){
    var gauge = $(this).find('th').eq(cell.index()).find('.gauge');
    var part = parseInt(gauge.attr('data-part')) + value;
    gauge.attr('data-part', part);
  });
}

liOC.gaugeInc = function(cell) {
  liOC.gaugeChange(cell, 1);
}

liOC.gaugeDec = function(cell) {
  liOC.gaugeChange(cell, -1);
}

liOC.disable = function() {
  $('.sf_admin_actions_block .fg-button')
    .addClass('ui-state-disabled')
    .off();
  $('.cell_choices')
    .removeClass('cell_choices')
    .off();
}

liOC.validate = function(url) {
  $('#transition').fadeIn('medium');
  
  var snapshots = liOC.createSnapshot();

  $.post(url, 
    {
      content: JSON.stringify(snapshots),
      name: $('#snapshot_name').val(),
      date: $('.plan_day th').eq(1).attr('data-date'),
      purpose: 'valid',
      _csrf_token: $('#_csrf_token').val()
    }, 
    function(data) {
      $('#transition .close').click();
      liOC.disable();
  });

}

liOC.loadSnapshot = function(url) {
  $.ajax({
    url: url,
    data: {},
    method: 'get',
    success: function(data){      
      $('.plan_body').html('');
      liOC.addPros(data);
      liOC.refreshGauges();
      liOC.fixTableScroll();
      liOC.blockContextMenu();
      $('#transition .close').click();
    },
    error: function(data){
      console.log(data);
    }
  });
}

liOC.createSnapshot = function () {
  var snapshots = [];
  
  $('.plan_body tr').each(function() {
    var contact = new Object();
    contact.id = $(this).find('th:first-child').attr('data-id');
    contact.name = $(this).find('th:first-child').text();
    contact.manifestations = [];
    $(this).find('td').filter('.none, .algo, .human').each(function() {
      var manifestation = new Object();
      manifestation.id = $('.plan_events th').eq($(this).index()-1).attr('data-id');
      manifestation.time_slot_id = $('.plan_hours th').eq($(this).index()-1).attr('data-grp-id');
      var gauge = $('.plan_gauges th').eq($(this).index()).find('.gauge');
      var current = gauge.attr('data-part');
      var max = gauge.attr('data-max');
      manifestation.gauge_free = max - current;
      manifestation.gauge_id = $('.plan_gauges th').eq($(this).index()).attr('data-id');
      manifestation.rank = $(this).text();
      var accepted = $(this).attr('class').match(/none|algo|human/g);
      if ( accepted.length > 0 )
        manifestation.accepted = accepted[0];
        
      contact.manifestations.push(manifestation);
    });
    snapshots.push(contact);
  });
  
  return snapshots;
}

liOC.saveSnapshot = function() {
  var snapshots = liOC.createSnapshot();
  
  $.post($('.save_snapshot_popup').attr('data-url'), 
    {
      content: JSON.stringify(snapshots),
      name: $('#snapshot_name').val(),
      date: $('.plan_day th').eq(1).attr('data-date'),
      purpose: 'save',
      _csrf_token: $('#_csrf_token').val()
    }, 
    function(data) {
      console.log(data);
      $('#transition .close').click();
  });
}

liOC.refreshGauges = function() {
  
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

liOC.loadDay = function(data, length) {
  var header_gauges = liOC.createHeaderCell()
    .prop('rowspan', 3)
    .appendTo('.plan_day');
    
  var header_day = liOC.createHeaderCell()
    .prop('colspan', length)
    .attr('data-date', data.current.date)
    .appendTo('.plan_day');
       
  var previous = $('<div class="plan_previous floatleft"></div>').appendTo(header_day);
  var next = $('<div class="plan_next floatright"></div>').appendTo(header_day);

  if ( data.previous.day ) {
    liOC.createLinkDay()
      .text(data.previous.day)
      .appendTo(previous)
      .prop('href', '?date='+data.previous.date);
  }
  if ( data.next.day ) {
    liOC.createLinkDay()
      .text(data.next.day)
      .appendTo(next)
      .prop('href', '?date='+data.next.date);
  }

  $('<span></span>').appendTo(header_day).text(data.current.day);
}

liOC.loadHours = function(data) {

  var i = 0;

  $.each(data, function(key, manifestation) {
    var header_hours = liOC.createHeaderCell()
      .prop('colspan', manifestation.events.length)
      .attr('data-grp-id', manifestation.time_id)
      .attr('data-min', i)
      .appendTo('.plan_hours'); 
    $('<span></span>').appendTo(header_hours).text(manifestation.range);
    
    $.each(manifestation.events, function(key, event) {
      i++;
      var header_events = liOC.createHeaderCell()
        .attr('data-id', event.id)
        .attr('data-grp-id', manifestation.time_id)
        .appendTo('.plan_events'); 
      $('<span></span>').appendTo(header_events).text(event.name);
      
      var header_gauges = liOC.createHeaderCell()
        .attr('data-id', event.gauge.id)
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

liOC.addPros = function(data) {
  $('.plan_gauges th .gauge').attr('data-part', 0);
  
  $.each(data, function(i, pro) {
    var row_pro = $('<tr class="sf_admin_row ui-widget-content"></tr>')
      .appendTo('.plan_body');
    if ( i%2 ) {
      row_pro.addClass('odd');
    }
    
    row_pro.append(
        $('<th></th>')
            .append($('<input>').prop('type', 'hidden').prop('name', 'rank[]').val(pro.id).addClass('rank'))
            .append($('<span>').addClass('rank').attr('data-rank', pro.rank).text(pro.rank))
            .append($('<span>').addClass('name').text(pro.name))
            .append('<br/>')
            .append($('<span>').addClass('organism').text(pro.organism))
            .attr('data-id', pro.id)
    );

    $('.plan_events th').each(function() {
      var manif_cell = $('<td></td>')
        .appendTo(row_pro);
        
      if ( !liOC.valid ) {
        manif_cell.addClass('cell_choices')
        .click(function() {
          var group_id = $('.plan_events th').eq($(this).index()-1).attr('data-grp-id');
          var group = $('.plan_hours th[data-grp-id='+group_id+']');
          var min = parseInt(group.attr('data-min'))+2;
          var max = parseInt(group.attr('data-max'))+1;
          var previous_selected = $(this).closest('tr').find('td:nth-child(n+'+min+'):nth-child(-n+'+max+')').filter('.algo, .human');
          
          if ( previous_selected.length > 0 ) {
            previous_selected.removeClass('none algo human');
            liOC.gaugeDec(previous_selected);
          }

          if ( $(this).index() != previous_selected.index() ) {
            $(this).removeClass('none algo human');
            $(this).addClass('human');
            liOC.gaugeInc($(this));
          }

          liOC.refreshGauges();
        });
      }

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
                .addClass(liOC.choices[manif.rank])
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
    header_pos += parseInt($(this).prop('colspan'));
  });
  
  liOC.sortPros();
}

liOC.computeRanks = function(elt) {
  var rank = 1;
  $(elt).find('tr').each(function(){
    $(this).find('.rank').text(rank).attr('data-rank', rank);
    rank++;
  });
  $('.sf_admin_actions_block .ranks a').click();
  
  // select / sortable
  setTimeout(function() {
      $('.sf_admin_list table tbody tr.ui-state-highlight').removeClass('ui-state-highlight');
  },500);
  liOC.lastClickedLine = undefined;
}

liOC.selectPros = function() {
  $('.sf_admin_list table.real tbody tr > th').click(function(e){
    liOC.selectPro(e, this);
  });
}
liOC.selectPro = function(e, elt) {
    if ( e.shiftKey && liOC.lastClickedLine != undefined ) {
        var start = 0;
        var stop  = 0;
        if ( liOC.lastClickedLine.index() < $(elt).closest('tr').index() ) {
            // down
            start = liOC.lastClickedLine.index()+1;
            stop  = $(elt).closest('tr').index();
        }
        else {
            // up
            start = $(elt).closest('tr').index();
            stop  = liOC.lastClickedLine.index()-1;
        }
        for ( var i = start ; i <= stop ; i++ ) {
            var tr = $($(elt).closest('tbody').find('tr')[i]);
            tr.toggleClass('ui-state-highlight');
            $('.sf_admin_list table.th-clone tbody tr > th[data-id="'+tr.find('[data-id]').attr('data-id')+'"]').toggleClass('ui-state-highlight');
        }
    }
    else {
        $(elt).closest('tr').toggleClass('ui-state-highlight');
        $('.sf_admin_list table.th-clone tbody tr > th[data-id="'+$(elt).attr('data-id')+'"]').toggleClass('ui-state-highlight');
    }
    liOC.lastClickedLine = $(elt).closest('tr');
}

liOC.sortPros = function() {
  liOC.selectPros();
  
  var index = 0;
  $('.sf_admin_list table.real tbody').sortable({
    cursor: 'move',
    items: 'tr',
    delay: 150,
    axis: 'y', 
    start: function(event, ui){
        index = $('.sf_admin_list table.real tbody .ui-state-highlight').index(ui.item);
    },
    update: function(event, ui){
      // a trick for multisortable fake feature...
      $('.sf_admin_list table.real tbody .ui-state-highlight').insertAfter(ui.item);
      $(ui.item).insertAfter($('.sf_admin_list table.real tbody .ui-state-highlight')[index]);
      
      liOC.computeRanks(this);
      liOC.fixTableScroll();
    }
  });
  
  $('#sf_admin_content .sf_admin_actions_block .ranks a').click(function(){
    $.ajax({
      url: $(this).prop('href'),
      method: 'post',
      data: $(this).closest('form').serialize(),
      complete: function(){
        $('#transition .close').click();
      }
    });
    return false;
  });
}

liOC.loadPros = function(length, date) {
  $.ajax({
    url: $('.plan_body').attr('data-url') + (date !== undefined ? '?date=' + date : ''),
    data: {},
    method: 'get',
    success: function(data){
      liOC.addPros(data);
      liOC.fixTableScroll();
      $(document).on('contextmenu', 'td', function(e) {
        e.preventDefault();
        return false;
      });
    },
    error: function(data){
      console.log(data);
    }
  });
}

liOC.initGUI = function() {
  $('.plan_body').html('');
  $('.plan_header tr').remove('> *:not(.participants)');
}

liOC.loadHeaders = function(date) {
  $.ajax({
    url: $('.plan_header').attr('data-url') + (date !== undefined ? '?date=' + date : ''),
    data: {},
    method: 'get',
    success: function(data){      
      liOC.initGUI();
      liOC.loadDay(data, data.length);
      liOC.loadHours(data.manifestations);
      liOC.loadPros(data.length, date);
      liOC.refreshGauges();
      
      if ( liOC.valid ) {
        liOC.disable();
      }
    },
    error: function(data){
      console.log(data);
    }
  });
}
