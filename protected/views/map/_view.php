<?php
$width = isset($width) ? $width : 1200;
$height = isset($height) ? $height : null;
$navigation = isset($navigation) ? $navigation : true;
$dataUrl = isset($dataUrl) ? $dataUrl : Yii::app()->createUrl("/map/listHosts");

$hostTypes = Host::getTypes();
?>
<div id="netmap" style="width: <?php echo $width; ?>px;">
    <div id="menuhosts">
        <input type="text" name="hostfilter" id="hostfilter" style="display: none" />
        <div id="listhosts">
        </div>
    </div>
</div>

<script type="text/javascript">
    var mapData, force, links, hosts, labels, infobox,
<?php if ($height == null): ?>
        h = (window.screen.height) ? window.screen.height - 320 : 600, // svg height auto
<?php else: ?>
        h = <?php echo $height; ?>, // svg height
<?php endif; ?>
    w = <?php echo $width; ?> - 160    // svg width - menu
    mapDataURL = '<?php echo $dataUrl ?>';

    var navigation = <?php echo (bool) $navigation ?>;


    // --------------------------------------------------------

    var netmap = d3.select("#netmap");
    var netmapListHost = d3.select("#listhosts");

    function loadMap(url) {

        netmap.selectAll('svg')
                .attr("opacity", 1)
                .transition()
                .duration(300)
                .attr("opacity", 1e-6)
                .remove();

        vis = netmap.append("svg:svg")
                .attr("width", w)
                .attr("height", h)
                .attr("pointer-events", "all")
                .append('svg:g')
                .call(d3.behavior.zoom().on("zoom", redraw))
                .append('svg:g');

        // background to zoom
        vis.append('svg:rect')
                .attr('width', w * 3)
                .attr('height', h * 3)
                .attr('x', -h / 2)
                .attr('y', -w / 2)
                .attr('fill', 'white');

        d3.json(url, function(json) {

            mapData = json;

            if (mapData == null) {
                netmap.innerHTML = 'Erro ao carregar mapa';
                return;
            } else if (mapData.error != null) {
                netmap.innerHTML = 'Erro: ' + mapData.error;
                return;
            }

            // Compute the distinct nodes from the links.
            if (mapData.links) {
                mapData.links.forEach(function(link) {
                    link.source = mapData.nodes[link.source] || (mapData.nodes[link.source] = {name: link.source});
                    link.target = mapData.nodes[link.target] || (mapData.nodes[link.target] = {name: link.target});
                });
            }

            init();

            vis.attr("opacity", 1e-6)
                    .transition()
                    .duration(1000)
                    .attr("opacity", 1);

            showListHosts();
        });
    }

// get host info on port from netmap
    function showListHosts() {
        netmapListHost.selectAll("div")
            .data(force.nodes())
            .enter()
            .append("a")
                .attr("class", function(d) {
                    return "view host-type "+ d.type;
                })
                .attr("href","#")
                .on('click', function (d) {
                    showPort(d)
                })
                .attr('title', function (d) {
                    return d.href ? d.name +' (double click here will open this host)' : ''
                })
                .on('dblclick', function (d) {
                    if(d.href) window.location.href = d.href
                })
                .html(function(d) {return d.name;});
        netmapListHost.style('height', (h - 35)+'px');
    }

    function filterListHosts() {
        slideTime = 500;
        listHosts = $("#listhosts a");
        if (this.value) {
            listHosts.filter(":not(:contains('" + this.value + "'))").slideUp(slideTime);
            listHosts.filter(":contains('" + this.value + "')").slideDown(slideTime);
        } else {
            listHosts.slideDown(slideTime);
        }

    }

    function init() {

        if (force)
            force.stop();

        // center primary node
        rootHost = mapData.nodes[mapData.rootHostName];
        rootHost.fixed = true;
        rootHost.x = w / 2;
        rootHost.y = h / 2;

        force = self.force = d3.layout.force()
                .nodes(d3.values(mapData.nodes))
                .links(d3.values(mapData.links))
                .size([w, h])
                .linkDistance(90)
                .charge(-520)
                .friction(0.8)
                //.linkStrength(0.2)
                .gravity(0.1)
                //.charge(-2000)
                //.theta(0.9)
                .start();

        links = vis.selectAll("line.link")
                .data(force.links())
                .enter().append("svg:line")
                .attr("class", function(d) {
                    return "link " + d.type + " port" + d.srcPort;
                })
                .attr("title", function(d) {
                    return 'port:' + d.srcPort + ' type:' + d.type;
                })
                .attr("x1", function(d) {
                    return d.source.x;
                })
                .attr("y1", function(d) {
                    return d.source.y;
                })
                .attr("x2", function(d) {
                    return d.target.x;
                })
                .attr("y2", function(d) {
                    return d.target.y;
                })

        hosts = vis.selectAll("g.host")
                .data(force.nodes())
                .enter().append("svg:g")
                .attr("class", function(d) {
                    return "host " + d.type
                })
                .on('mouseover', showHostInfoBox)
                .on('mouseout', hideHostInfoBox)
                .on("click", function(d) {
                    if (navigation) {
                        return showPort(d)
                    } else {
                        return false
                    }
                })
                .on("dblclick", function(d) {
                    if(d.href) window.location.href = d.href;
                });

        //.call(force.drag);

        // host info
        hosts.append('svg:rect')
                .attr('class', 'infobox')
                .attr('visibility', 'hidden')
                .attr('height', '32px')
                .attr('x', '-18px')
                .attr('y', '-18px')
                .attr('fill', 'yellow');

        // host images        
        hosts.append("svg:image")
                .attr("xlink:href", function(d) {
                    var image = (d.type) ? d.type : 'unknown';
                    return "<?php echo Yii::app()->baseUrl; ?>/images/host/" + image + ".png"
                })
                .attr("width", "32px")
                .attr("height", "32px")
                .attr("x", "-16px")
                .attr("y", "-16px");

        // host labels
        hosts.append("svg:text")
                .attr("class", "host-label")
                .attr("x", 17)
                .attr("y", ".31em")
                .text(function(d) {
                    return d.name;
                });

        force.on("tick", function() {

            links.attr("x1", function(d) {
                return d.source.x;
            })
                    .attr("y1", function(d) {
                        return d.source.y;
                    })
                    .attr("x2", function(d) {
                        return d.target.x;
                    })
                    .attr("y2", function(d) {
                        return d.target.y;
                    });

            hosts.attr("transform", function(d) {
                return "translate(" + d.x + "," + d.y + ")";
            });

        });

        $('#hostfilter').on('keyup', filterListHosts);
        $('#hostfilter').slideDown(500);
    }

    function showPort(portData) {
    
        var portNumber = null;

        if (typeof (showPortInfo) === 'function') {
            mapData.links.forEach(function(link) {
                if (link.target.name === portData.name) {
                    portNumber = link.srcPort;
                    return;
                }
            });

            if (portNumber) {
                showPortInfo(portNumber);
                return;
            }
        }

        if (portData.href) {
            window.location.href = portData.href;
        }
    }

    function redraw() {
        //console.log("here", d3.event.translate, d3.event.scale);
        vis.attr("transform",
                "translate(" + d3.event.translate + ")" +
                " scale(" + d3.event.scale + ")");
    }

    function showHostInfoBox() {

        var boxSVG = this.getBBox();
        var box = d3.select(this).select('.infobox');

        box.attr('width', (boxSVG.width) + 'px')
                .attr('visibility', 'visible');

        // animation
        box.attr("opacity", 1e-6)
                .transition()
                .duration(300)
                .attr("opacity", 1);

    }

    function hideHostInfoBox() {
        var box = d3.select(this).select('.infobox');
        box.attr('visibility', 'hidden');
    }

    //start
    loadMap(mapDataURL);

</script>