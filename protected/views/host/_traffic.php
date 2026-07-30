<?php
if ($model instanceof Host && !empty($model->snmpTemplate)):

    ?>
    <div style="float:right">
        <input id="ckbxRefreshTraffic" type="checkbox" onclick="ajaxLoadStatus = this.checked" checked="checked" /> 
        <label for="ckbxRefreshTraffic" class="inline">refresh</label>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <div class="bullet">
        <span class="measure s0" style="width:10px; height:10px">&nbsp;&nbsp;&nbsp;&nbsp;</span> In
        <span class="measure s1" style="width:10px; height:10px">&nbsp;&nbsp;&nbsp;&nbsp;</span> Out
        <span class="marker s0" style="width:10px; height:10px">&nbsp;&nbsp;&nbsp;&nbsp;</span> Last In
        <span class="marker s1" style="width:10px; height:10px">&nbsp;&nbsp;&nbsp;&nbsp;</span> Last Out
        </div>
    </div>
        <br clear="all" />
        
    <div id="trafficmap" class="well" style="width: 800px; margin-top: 20px;"></div>

    <script type="text/javascript" src="<?php echo Yii::app()->createUrl("/js/d3/bullet.js") ?>">
    </script>
    <script type="text/javascript" src="<?php echo Yii::app()->createUrl("/js/portTraffic.js") ?>">
    </script>
    <script type="text/javascript">

        var hostname = '<?php echo $model->name ?>';
        var svg = null;
        var requestTraffic = null;
        var portsInfoUrl = '<?php echo Yii::app()->createUrl("/host/loadPortInfo/" . $model->id) ?>';
        var portsInfo = [];
        var trafficUrl = '<?php echo Yii::app()->createUrl("/host/loadPortTraffic/" . $model->id) ?>';
        var portsTraffic = [];
        var ajaxLoadStatus = true;
        var loadTrafficInterval = 3000;
        var actionViewHostURL = '<?php echo Yii::app()->createUrl("/"); ?>/host/traffic/';
        
        function linkToHost(host) {
            return actionViewHostURL + host.name;
        }

        function refreshData() {
            clearInterval(requestTraffic);
            if (ajaxLoadStatus) {
                d3.json(trafficUrl, function(error, json) {
                    if (error) {
                        return console.warn(error);
                    }
                    portsTraffic = json;
                    svg.datum(setNewData).call(chart.duration(1000));
                });
            }
            requestTraffic = setTimeout(refreshData, loadTrafficInterval);
        }

        function getPortTraffic(index) {
            for(var i = 0; i < portsTraffic.ports.length; i++) {
                if (portsTraffic.ports[i].ifIndex == index) {
                    return portsTraffic.ports[i];
                }
            }
        }
        function setNewData(portData) {
            var portDataNew = getPortTraffic(portData.ifIndex);
            return PortTraffic.updateSample(portData, portDataNew, portsTraffic.time);
        }

        function setIfAliasSNMP(d) {
            value = prompt(d.title + ' alias:', d.subtitle);
            obj = this;
            if (value && value != d.subtitle) {

                $.post("<?php echo Yii::app()->createUrl("/host/setSNMP") ?>",
                        {name: hostname, key: 'ifAlias', index: d.ifIndex, value: value}
                ).done(function() {
                    d.subtitle = value;
                    obj.innerHTML = value;
                });

            }

            return d;

        }

        var margin = {top: 2, right: 20, bottom: 20, left: 200},
        width = 780 - margin.left - margin.right,
                height = 40 - margin.top - margin.bottom;

        var chart = d3.bullet()
                .width(width)
                .height(height)
                .tickFormat(function(d) {
                    if (d >= 1000000000) {
                        return (d / 1000000000) + " G";
                    }
                    return (d / 1000000) + " M";
                });

        d3.json(portsInfoUrl, function(error, json) {
            if (error) {
                return console.warn(error);
            }
            
            // info bullet chart
            json.forEach(function(port) {
                port.title = port.ifDescr;
                port.subtitle = port.ifAlias ? port.ifAlias : '""';
                port.ranges = [0,0];
                port.measures = [0,0];
                port.markers = [0,0];
                portsInfo.push(port);
            });

            svg = d3.select("#trafficmap").selectAll("svg")
                    .data(portsInfo)
                    .enter().append("svg")
                    .attr("id", function (d){ return "bullet"+d.ifIndex })
                    .attr("class", "bullet")
                    .attr("width", width + margin.left + margin.right)
                    .attr("height", height + margin.top + margin.bottom)
                    .append("g")
                    .attr("transform", "translate(" + margin.left + "," + margin.top + ")")
                    .call(chart);

            var title = svg.append("g")
                    .style("text-anchor", "end")
                    .attr("transform", "translate(-6," + height / 2 + ")");

            title.append("text")
                    .attr("class", function(d) {
                        c = 'title';
                        if (d.hasConnection) c += ' hasConnection';
                        return c;
                    })
                    .attr("title", function(d) {
                        if (d.hasConnection) return 'Connection to: ' + d.hasConnection.name;
                    })
                    .text(function(d) {
                        return d.title;
                    })
                    .on("dblclick", function(d) {
                        if(d.hasConnection) window.location.href = linkToHost(d.hasConnection);
                    });

            title.append("text")
                    .attr("class", "subtitle")
                    .attr("dy", "1em")
                    .text(function(d) {
                        return d.subtitle;
                    })
                    .on('dblclick', setIfAliasSNMP);

            d3.select("#btRefresh").on("click", refreshData);

            refreshData();

        });
    </script>
<?php endif; // instanceof host     ?>
