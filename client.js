function nice_days(days)
{
    return days.toFixed(1)
        .replace(/\.0$/, '')
        .replace(/\.5$/, '½')
        .replace(/^0/, '');
}

function render_client(data, info)
{
    var total = 0,
        last = null;
    
    var start = {'days': 0, 'time': data[0].time - 7*86400, 'week': ''};
    data.unshift(start);
    
    for(var i = 0; i < data.length; i++)
    {
        total += data[i].days;
        data[i].total = total;
        last = data[i];
    }
    
    var w = 960,
        h = 390,
        end_time = Math.max(last.time, info.time),
        total_days = (end_time - data[0].time) / 86400,
        x = pv.Scale.linear(start.time, end_time).range(0, w),
        y = pv.Scale.linear(0, Math.max(total, info.days)).range(0, h),
        small = '13px Georgia',
        large = '18px Georgia';
    
    var vis = new pv.Panel()
        .width(w)
        .height(h)
        .left(40)
        .right(25)
        .bottom(30)
        .top(40);
    
    //
    // area of profitability
    //
    vis.add(pv.Area)
        .data([{time: start.time, total: 0}, {time: info.time, total: info.days}])
        .left(function(d) { return x(d.time) })
        .height(function(d) { return y(d.total) })
        .bottom(0)
        .fillStyle('#f4f4f4');
    
    //
    // weekly vertical rules
    //
    vis.add(pv.Rule)
        .data(data)
        .strokeStyle('#ccc')
        .left(function(d) { return x(d.time) })
        .height(function(d) { return y(d.total) })
        .bottom(y(0));
    
    //
    // bottom rule
    //
    vis.add(pv.Rule)
        .bottom(y(0))
        .strokeStyle('#ccc')
        .left(0)
        .right(0);
    
    //
    // top rule
    //
    vis.add(pv.Rule)
        .bottom(y((info.days)))
        .strokeStyle('#f90')
        .lineWidth(2)
        .left(0)
        .right(x(Math.max(info.time, last.time)) - x(info.time));
    
    //
    // left-hand rule
    //
    vis.add(pv.Rule)
        .left(x(start.time))
        .strokeStyle('#ccc')
        .bottom(0)
        .top(0)
      .add(pv.Label)
        .left(4)
        .top(h - y(info.days) + 24)
        .text(nice_days(info.days) + ' days')
        .textAlign('left')
        .font(large);
    
    //
    // left hand ticks
    //
    vis.add(pv.Rule)
        .data(y.ticks())
        .visible(function() { return this.index > 0 })
        .strokeStyle('#ccc')
        .bottom(y)
        .left(-5)
        .width(5)
      .anchor('left').add(pv.Label)
        .text(y.tickFormat)
        .font(small);
    
    //
    // right-hand rule and label
    //
    vis.add(pv.Rule)
        .left(x(info.time))
        .strokeStyle('#f90')
        .lineWidth(2)
        .bottom(0)
        .height(y(info.days))
      .add(pv.Label)
        .top(h - 8)
        .left(x(info.time) - 4)
        .text('Ends ' + info.date)
        .textAlign('right')
        .font(large);
    
    //
    // weekly time
    //
    vis.add(pv.Line)
        .data(data)
        .left(function(d) { return x(d.time) })
        .bottom(function(d) { return y(d.total) })
        .strokeStyle('#fafafa')
        .lineWidth(10)
      .add(pv.Line)
        .data(data)
        .left(function(d) { return x(d.time) })
        .bottom(function(d) { return y(d.total) })
        .strokeStyle('#666')
        .lineWidth(4)
      .add(pv.Dot)
        .size(function(d) { return (this.index > 0) ? 40 : 20 })
        .strokeStyle(function(d) { return (this.index > 0) ? '#fafafa' : '#fff' })
        .fillStyle('#666')
      .anchor('top').add(pv.Label)
        .text(function(d) { return nice_days(d.total); })
        .visible(function() { return this.index > 0 })
        .textAlign('right')
        .textAngle(total_days > 160 ? 0.393 : 0.000)
        .font(large);
    
    //
    // weekly dates
    //
    vis.add(pv.Label)
        .data(data)
        .left(function(d) { return x(d.time) + 8 })
        .bottom(total_days > 160 ? -15 : -20)
        .text(function(d) { return d.date })
        .textAlign('right')
        .textAngle(total_days > 160 ? -0.393 : 0.000)
        .font(small);
    
    //
    // pig
    //
    vis.add(pv.Panel)
        .width(46)
        .height(46)
        .left(x(info.time) - 23)
        .bottom(y(info.days) - 23)
      .add(pv.Image)
        .url('pig.png')
    
    //
    // bird
    //
    vis.add(pv.Panel)
        .width(41)
        .height(35)
        .left(x(last.time) - 20)
        .bottom(y(last.total) - 20)
      .add(pv.Image)
        .url('bird.png')
    
    vis.render();
}
