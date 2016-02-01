<?php

// TODO: fazer modo de edicao dos hosts e links no mapa

$legenda = isset($legenda) ? $legenda : false;
$width = isset($width) ? $width : 900;
$height = isset($height) ? $height : 'auto';
$dataUrl = isset($dataUrl) ? $dataUrl : Yii::app()->createUrl("/map/listHosts");

?>
<link rel="stylesheet" href="<?php echo Yii::app()->baseUrl; ?>/css/map.css" type="text/css" />
<script type="text/javascript" src="<?php echo Yii::app()->baseUrl; ?>/js/d3/d3.v2.js"></script>

<div id="netmap" style="height: <?php echo $height; ?>px; width: <?php echo $width; ?>px;">
    <?php if ($legenda === true): ?>
    <div class="legenda">
        Legenda:
        <table border="0">
            <tr>
                <td><img class="icon" src="<?php echo Yii::app()->baseUrl; ?>/images/host/switch.png" alt="img" /></td>
                <td>switch</td>
            </tr>
            <tr>
                <td><img class="icon" src="<?php echo Yii::app()->baseUrl; ?>/images/host/router.png" alt="img" /></td>
                <td>router</td>
            </tr>
            <tr>
                <td><img class="icon" src="<?php echo Yii::app()->baseUrl; ?>/images/host/server.png" alt="img" /></td>
                <td>server</td>
            </tr>
        </table>
    </div>
<?php endif; ?>
</div>

<script type="text/javascript">
    var data, force, links, hosts, labels, box,
<?php if ($height == 'auto'): ?>
            h = (window.screen.height) ? window.screen.height - 320 : 600,     // svg height
<?php else: ?>
            h = <?php echo $height; ?>,     // svg height
<?php endif; ?>
        w = <?php echo $width; ?>     // svg width
    
   
        // --------------------------------------------------------

        var body = d3.select("#netmap");

        function loadMap(url){
            
            body.selectAll('svg')
            .attr("opacity", 1)
            .transition()
            .duration(300)
            .attr("opacity", 1e-6)
            .remove();
        
            vis = body.append("svg:svg")
            .attr("width", w)
            .attr("height", h)
            .attr("pointer-events", "all")
            .append('svg:g')
            .call(d3.behavior.zoom().on("zoom", redraw))
            .append('svg:g');

            // background to zoom
            vis.append('svg:rect')
            .attr('width', w*3)
            .attr('height', h*3)
            .attr('x', -h/2)
            .attr('y', -w/2)
            .attr('fill', 'white');
            
            d3.json(url, function(json) {

                data = json;
 
                // Compute the distinct nodes from the links.
                data.links.forEach(function(link) {
                    link.source = data.nodes[link.source] || (data.nodes[link.source] = {name: link.source});
                    link.target = data.nodes[link.target] || (data.nodes[link.target] = {name: link.target});
                });
    
                init();

                vis.attr("opacity", 1e-6)
                .transition()
                .duration(1000)
                .attr("opacity", 1);
            
            });
        }

    
        function init() {
        
            if (force) force.stop();
        
            // center primary node
            rootNode = data.nodes[0];
            rootNode.fixed = true;
            rootNode.x = w/2;
            rootNode.y = h/2;

            force = self.force = d3.layout.force()
            .nodes(data.nodes)
            .links(data.links)
            .size([w, h])
            .linkDistance(90)
            .charge(-520)
            .friction(0.8)
            //        .linkStrength(0.2)
            .gravity(0.1)
            //        .charge(-2000)
            //        .theta(0.9)
            .start();

            links = vis.selectAll("line.link")
            .data(data.links)
            .enter().append("svg:line")
            .attr("class", function(d) { return "link " + d.type; })
            .attr("x1", function(d) { return d.source.x; })
            .attr("y1", function(d) { return d.source.y; })
            .attr("x2", function(d) { return d.target.x; })
            .attr("y2", function(d) { return d.target.y; })

            hosts = vis.selectAll("g.host")
            .data(data.nodes)
            .enter().append("svg:g")
            .attr("class", "host")
            .attr("style", "border: 1px solid red")
            .on("click", function(d){ loadMap(d.href) })
            //        .call(force.drag);

            // host images        
            hosts.append("svg:image")
            .attr("xlink:href", function(d){ 
                return "<?php echo Yii::app()->baseUrl; ?>/images/host/"+d.type+".png"
            })
            .attr("x", "-16px")
            .attr("y", "-16px")
            .attr("width", "32px")
            .attr("height", "32px");

            // host labels
            hosts.append("svg:text")
            .attr("class","host-label")
            .attr("x", 10)
            .attr("y", ".31em")
            .text(function(d) { return d.name; });

            force.on("tick", function() {

                links.attr("x1", function(d) { return d.source.x; })
                .attr("y1", function(d) { return d.source.y; })
                .attr("x2", function(d) { return d.target.x; })
                .attr("y2", function(d) { return d.target.y; });

                hosts.attr("transform", function(d) { 
                    return "translate(" + d.x + "," + d.y + ")"; 
                });
     
            });
        }
    
        function openLink(obj){
            if(obj.href == null) return;
            window.location.href = obj.href;
        }
    
        function redraw() {
            //console.log("here", d3.event.translate, d3.event.scale);
            vis.attr("transform",
            "translate(" + d3.event.translate + ")"+
                " scale(" + d3.event.scale + ")");
        }

        //start
        loadMap('<?php echo $dataUrl ?>');

</script>