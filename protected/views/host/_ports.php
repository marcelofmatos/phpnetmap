<?php
if ($model instanceof Host && !empty($model->snmpTemplate)):
    ?>
    <div id="info" style="float:right">
        <input id="ckbxRefreshStatus" type="checkbox" onclick="ajaxLoadStatus = this.checked" checked="checked" /> 
        <label for="ckbxRefreshStatus" class="inline">refresh</label>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <span class="port ifAdminStatus2" style="width:10px; height:10px">&nbsp;&nbsp;&nbsp;&nbsp;</span> Disabled
        <span class="port ifOperStatus1" style="width:10px; height:10px">&nbsp;&nbsp;&nbsp;&nbsp;</span> Active
        <span class="port ifOperStatus1 dot1dStpPortState2" style="width:10px; height:10px">&nbsp;&nbsp;&nbsp;&nbsp;</span> STP Block
        <span class="port ifOperStatus2" style="width:10px; height:10px">&nbsp;&nbsp;&nbsp;&nbsp;</span> Inactive
    </div>

    <h3>Ports: </h3>


    <div id="hostFace">
        <?php if ($model->hostFace): ?>
            <?php echo $model->hostFace->svg; ?>
        <?php endif; ?>
    </div>


    <script type="text/javascript">
        // load interfaces status
        var ajaxLoadStatus = true; // load status by ajax
        var requestRunning = false;
        var requestInterval = 2000;  // interface status load interval (ms)
        var requestStatus = null;
        var portStatusURL = '<?php echo Yii::app()->createUrl("host/loadPortStatus/" . $model->id); ?>';
        var portsInfoURL = '<?php echo Yii::app()->createUrl("host/loadPortInfo/" . $model->id); ?>';
        var portInfoBox = null;
        var selectedPort = null;
        var allPorts = null;
        var actionViewHostURL = '<?php echo Yii::app()->baseUrl; ?>/host/view/';


        function drawPorts(portsData){
            // TODO: desenhar hostFace com svg
            hostFace = d3.select('#hostFace')
                    .append('div')
                    .attr('class','portbox')
                    .style('width',function(){
                        return (portsData.length <= 24) ? '529px' : '705px';
                    });
            
            svg = hostFace.selectAll('div')
                    .data(portsData)
                    .enter()
                    .append('span')
                        .attr('id',function(d){ return 'port'+d.ifIndex })
                        .attr('class','port')
                        .attr('title',function(d){ 
                            return d.ifDescr + (d.ifAlias ? ' ('+ d.ifAlias + ')' : '') 
                        })
                        .html(function(d){ return d.ifIndex })
        }
        
        function loadPortInfo() {
            d3.json(portsInfoURL, function (error, json) {
                if (error) {
                    return console.warn(error);
                }
                
                hostFace = d3.selectAll('#hostFace svg, #hostFace div');
                
                if(hostFace.empty()) {
                    drawPorts(json); 
                }

                json.forEach(function (d) {
                    
                    port = d3.select('#hostFace #port' + d.ifIndex);
                    if (port.empty()) return;
                    portData = port.datum();
                    if(portData) {
                        d = $.extend(portData, d);
                    }
                    port.data([d])
                        .attr('title', d.hasConnection ? 'Link to: '+ d.hasConnection.name +' (double click here will open this host)' : 'Port '+ d.ifDescr)
                        .on('click', function (d) {
                            showPortInfo(d.ifIndex)
                        });

                });

            });
        }

        function loadPortStatus() {
            
            clearTimeout(requestStatus);
            
            if (ajaxLoadStatus && !requestRunning) {
                
                requestRunning = true;
                
                d3.json(portStatusURL, function (error, json) {
                    if (error) {
                        ajaxLoadStatus = false;
                        return console.warn(error);
                    }
                    
                    json.forEach(function (d) {
                        port = d3.select('#hostFace #port' + d.ifIndex)
                        if (port.empty()) return;
                        portData = port.datum();
                        if(portData) {
                            d = $.extend(portData, d);
                        }
                        port.data([d])
                            .attr('class', function () {
                                c = 'port'
                                if (d.ifOperStatus)
                                    c = c + ' ifOperStatus' + d.ifOperStatus;
                                if (d.ifAdminStatus)
                                    c = c + ' ifAdminStatus' + d.ifAdminStatus;
                                if (d.dot1dStpPortState)
                                    c = c + ' dot1dStpPortState' + d.dot1dStpPortState;
                                if (d.hasConnection)
                                    c = c + ' hasConnection';
                                return c;
                            })
                            .on('click', function (d) {
                                showPortInfo(d.ifIndex)
                            })
                            .on("dblclick", function(d) {
                                if(d.hasConnection) window.location.href = linkToHost(d.hasConnection);
                            });

                    });

                    if (selectedPort) {
                        selectedPort.classed('hover', true);
                    }
                    requestRunning = false;
                });
            }

            requestStatus = setTimeout(loadPortStatus, requestInterval);

        }

        function reloadPortStatus() {
            clearTimeout(requestStatus);
            ajaxLoadStatus = true;
            loadPortStatus();
            ajaxLoadStatus = $('#ckbxRefreshStatus').attr('checked');
            requestStatus = setTimeout(loadPortStatus, requestInterval);
        }

        // port menu
        function showPortInfo(portNumber) {
            
            allPorts = d3.selectAll('#hostFace .port');
            selectedPort = d3.select('#port'+portNumber);
            if(selectedPort.empty()) {
                console.warn('Error loading port '+portNumber);
                return;
            }
            portData = selectedPort.datum();

            if (portData.ifIndex) {

                //portInfoBox.hide();
                allPorts.classed('hover', false);
                selectedPort.classed('hover', true);

                // set port info form
                $('#ifDescr').html(portData.ifDescr ? portData.ifDescr : "ID " + portData.ifIndex);
                $('#ifAlias').val(portData.ifAlias ? portData.ifAlias : '');
    //                $('#ifSpeed').val(portData.ifSpeed ? portData.ifSpeed : '');
                $('#ifOperStatus').val(portData.ifOperStatus ? portData.ifOperStatus : 0);
                $('#ifAdminStatus').val(portData.ifAdminStatus ? portData.ifAdminStatus : 0);
                $('#ifIndex1').val(portData.ifIndex);
                $('#ifIndex2').val(portData.ifIndex);
                $('#hostSrcPort').val(portData.ifIndex);
                $('#dot1dStpPortState').val(portData.dot1dStpPortState ? portData.dot1dStpPortState : 0);

                showPortConnections(portData);

                portInfoBox.slideDown(300);

            }

        }

        // get host info on port from netmap
        function showPortConnections(port) {

            var connOnPort = d3.selectAll('.link.port' + port.ifIndex +':not(.supposed_link)');
            var connContainer = d3.select('#connections');

            connContainer.html('');
            
            if (port.hasConnection) {
                connContainer.append('a')
                        .attr('class', 'view host-type ' + port.hasConnection.type)
                        .attr('href', linkToHost(port.hasConnection) )
                        .html(port.hasConnection.name);
            } else {
                connContainer.selectAll('div')
                        .data(connOnPort.data())
                        .enter()
                        .append('div')
                        .each(function(d) {
                            if(d.vlan) {
                                d3.select(this).append("a")
                                    .attr('class','vlan')
                                    .attr('href', '<?php echo Yii::app()->baseUrl; ?>/vlan/tag/'+ d.vlan.tag)
                                    .attr('title', 'VLAN '+ d.vlan.name)
                                    .style('color','#'+d.vlan.font_color)
                                    .style('background-color','#'+d.vlan.background_color)
                                    .html(d.vlan.tag);
                            }

                            d3.select(this).append("a")
                                .attr('class', 'view host-type ' + d.target.type)
                                .attr('href', linkToHost(d.target) )
                                .html(d.target.name);
                        })
            }
            
        }
        
        function linkToHost(host) {
            if(host.href) {
                return host.href;
            } else {
                if(this.target) {
                    return this.target.href;
                }
            }
        }

        function setSNMPValue(obj) {

            $.post(
                    obj.form.action,
                    $(obj.form).serialize()
                    );

            if (ajaxLoadStatus === false) {
                document.getElementById('ckbxRefreshStatus').click();
            }

            loadPortInfo();
            loadPortStatus();

        }

        // init
        $(document).ready(function () {
            portInfoBox = $('#portInfoBox');
            allPorts = d3.selectAll('#hostFace .port');
            loadPortInfo();
            loadPortStatus();
        });
    </script>

    <div id="portInfoBox" class="well well-small" style="display:none">
        <div class="nav nav-pills">
            Port <label id="ifDescr" class="inline"></label>
            <input id="btnClosePortBox" class="btn btn-mini" type="button" title="Close this box" onclick="$('#portInfoBox').slideUp();" value="close">
        </div>
        <table width="97%">
            <tr>
                <td width="40%" style="vertical-align:top">

                    <div>
                        <div>If. Alias:</div>
                        <form method="post" target="_setsnmp" action="<?php echo Yii::app()->createUrl('host/setSNMP') ?>" onsubmit="return null;">
                            <input type="text" name="value" id="ifAlias" onchange="setSNMPValue(this);" />
                            <input type="hidden" name="name" value="<?php echo $model->name ?>" />
                            <input type="hidden" name="key" value="ifAlias" />
                            <input type="hidden" name="index" value="" id="ifIndex1" />
                        </form>
                        <iframe name="_setsnmp" height="0" width="0"></iframe>
                    </div>

                    <div>
                        <div>Admin. Status:</div>
                        <form action="<?php echo Yii::app()->createUrl('host/setSNMP') ?>" method="post">
                            <input type="hidden" name="name" value="<?php echo $model->name ?>" />
                            <input type="hidden" name="key" value="ifAdminStatus" />
                            <input type="hidden" name="index" value="" id="ifIndex2" />
                            <select id="ifAdminStatus" name="value" onChange="setSNMPValue(this);">
                                <option class="ifAdminStatus" value=""></option>
                                <option class="ifAdminStatus1" value="1">UP(1)</option>
                                <option class="ifAdminStatus2" value="2">DOWN(2)</option>
                            </select>
                        </form>
                    </div>

                    <div>
                        <div>Oper. Status:</div>
                        <select id="ifOperStatus" class="readonly" readonly disabled>
                            <option class="ifOperStatus" value=""></option>
                            <option class="ifOperStatus1" value="1">UP(1)</option>
                            <option class="ifOperStatus2" value="2">DOWN(2)</option>
                        </select>
                    </div>

                    <div>
                        <div>Spanning Tree Status:</div>
                        <select id="dot1dStpPortState" class="readonly" readonly disabled>
                            <option class="dot1dStpPortState" value=""></option>
                            <option class="dot1dStpPortState1" value="1">Disabled(1)</option>
                            <option class="dot1dStpPortState2" value="2">Blocking(2)</option>
                            <option class="dot1dStpPortState3" value="3">Listening(3)</option>
                            <option class="dot1dStpPortState4" value="4">Learning(4)</option>
                            <option class="dot1dStpPortState5" value="5">Forwarding(5)</option>
                            <option class="dot1dStpPortState6" value="6">Broken(6)</option>
                        </select>
                    </div>
                    <!--
                                                <div>
                                                    <div>Speed:</div>
                                                    <select id="ifSpeed" class="readonly" readonly disabled>
                                                        <option class="ifSpeed" value=""></option>
                                                        <option class="ifSpeed0" value="0">0</option>
                                                        <option class="ifSpeed10000000" value="10000000">10 M</option>
                                                        <option class="ifSpeed100000000" value="100000000">100 M</option>
                                                        <option class="ifSpeed1000000000" value="1000000000">1000 M</option>
                                                    </select>
                                                </div>
                    -->

                    <div>
                        <div>Options:</div>
                        <ul class="actions">
                            <li>
                                <form name="frmCreateConnection" action="<?php echo Yii::app()->createUrl('connection/create') ?>" method="get">
                                    <input type="hidden" name="host_src_id" id="hostSrcId" value="<?php echo $model->id ?>" />
                                    <input type="hidden" name="host_src_port" id="hostSrcPort"/>
                                    <input type="submit" value="Create connection" class="btn btn-primary" />
                                </form>
                            </li>
                        </ul>
                    </div>

                </td>

                <td style="vertical-align: top">
                    Link to:
                    <div id="connections"></div>
                </td>
            </tr>
        </table>

    </div>

<?php endif; // instanceof host   ?>
