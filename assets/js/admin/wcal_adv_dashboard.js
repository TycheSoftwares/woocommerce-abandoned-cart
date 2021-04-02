jQuery( document ).ready( function() {
    jQuery.datepicker.setDefaults( jQuery.datepicker.regional[ "en-GB" ] );
    jQuery( "#wcal_start_date" ).datepicker({
      onSelect: function( date ) {
         //var date = jQuery('#wcal_start_date').datepicker('getDate');
         jQuery('#wcal_end_date').datepicker('option', 'minDate', date);
         setTimeout(function(){
              jQuery( "#wcal_end_date" ).datepicker('show');
            }, 16);     
        },
         maxDate: '0',
         changeMonth: true,
         changeYear: true,
         dateFormat: "yy-mm-dd" 
    } );

    jQuery( '#duration_select' ).change( function() {
      
      var group_name  = jQuery( '#duration_select' ).val();
        if ( jQuery(this).val() == "custom") {
          document.getElementById("wcal_start_end_date_div").style.display = "block";
        }
        if ( jQuery(this).val() != "custom" ) {
          document.getElementById("wcal_start_end_date_div").style.display = "none";
        }
    });

} );

jQuery( document ).ready( function() {
    jQuery( "#wcal_end_date" ).datepicker( {
         maxDate: '0',
         changeMonth: true,
         changeYear: true,
         dateFormat: "yy-mm-dd" } );

} );

var margin = {top: 20, right: 40, bottom: 80, left: 40},
    width  = 980 - margin.left - margin.right,
    height = 500 - margin.top - margin.bottom;

var x0 = d3.scale.ordinal().rangeRoundBands([0, width], .4);
var x1 = d3.scale.ordinal();

var y0 = d3.scale.linear().range([height, 0]);
var y1 = d3.scale.linear().range([height, 0]);

var color = d3.scale.ordinal().range(["#98abc5", "#d0743c"]);

var xAxis = d3.svg.axis()
    .scale(x0)
    .orient("bottom")
    .ticks(5);

var yAxisLeft = d3.svg.axis()
    .scale(y0)
    .orient("left")
    .tickFormat(function(d) { return parseInt(d) });

var yAxisRight = d3.svg.axis()
    .scale(y1)
    .orient("right")
    .tickFormat(function(d) { return parseInt(d) });

var svg = d3.select(".chartgraph").append("svg")
    .attr("width", width + margin.left + margin.right)
    .attr("height", height + margin.top + margin.bottom)
  .append("g")
    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

var data = wcal_graph_data.data;
console.log( data );
var dataset = [];

var keyNames = ['abandoned_amount','recovered_amount'];

for(i = 0; i < Object.keys(data).length; i++ ) {
  var date = Object.keys(data)[i];
  dataset[i] = {
    date: date,
    values: [
     {name: 'Abandoned Revenue', value: data[date][keyNames[0]]},
     {name: 'Recovered Revenue', value: data[date][keyNames[1]]}
    ]
  };
}

x0.domain(dataset.map(function(d) { return d.date; }));
x1.domain(['Abandoned Revenue','Recovered Revenue']).rangeRoundBands([0, x0.rangeBand()]);

y0.domain([0, d3.max(dataset, function(d) { return d.values[0].value; })]);
y1.domain([0, d3.max(dataset, function(d) { return d.values[1].value; })]);

// Ticks on x-axis and y-axis
svg.append("g")
    .attr("class", "x axis")
    .attr("transform", "translate(0," + height + ")")
    .call(xAxis)
  .selectAll("text")
    .attr("y", 0)
    .attr("x", 9)
    .attr("dy", ".35em")
    .attr("transform", "rotate(45)")
    .style("text-anchor", "start");

svg.append("g")
    .attr("class", "y0 axis")
    .call(yAxisLeft)
  .append("text")
    .attr("transform", "rotate(-90)")
    .attr("y", 6)
    .attr("dy", ".71em")
    .style("text-anchor", "end")
    .style("fill", "#98abc5")
    .text("Abandoned Revenue");

svg.select('.y0.axis')
  .selectAll('.tick')
    .style("fill","#98abc5")
    .append("text");

svg.append("g")
    .attr("class", "y1 axis")
    .attr("transform", "translate(" + width + ",0)")
    .call(yAxisRight)
  .append("text")
    .attr("transform", "rotate(-90)")
    .attr("y", -16)
    .attr("dy", ".71em")
    .style("text-anchor", "end")
    .style("fill", "#d0743c")
    .text("Recovered Revenue");

svg.select('.y1.axis')
  .selectAll('.tick')
    .style("fill","#d0743c");
// End ticks

var graph = svg.selectAll(".date")
    .data(dataset)
    .enter()
    .append("g")
      .attr("class", "g")
      .attr("transform", function(d) { return "translate(" + x0(d.date) + ",0)"; });

graph.selectAll("rect")
    .data(function(d) { return d.values; })
    .enter()
    .append("rect")
      .attr("width", x1.rangeBand())
      .attr("x", function(d) { return x1(d.name); })
      .attr("y", function(d) { return y0(d.value); })
      .attr("height", function(d) { return height - y0(d.value); })
      .style("fill", function(d) { return color(d.name); });

graph.selectAll("rect")
    .on("mouseover", function(d){

      var delta = d.value;

      var xCord = d3.select(this.parentNode).attr("transform");
      xCord = xCord.replace('translate(', '');
      xCord = xCord.replace(',0)', '');

      var xPos = parseFloat(xCord) + parseFloat(d3.select(this).attr("x"));
      var yPos = parseFloat(d3.select(this).attr("y"));
      var height = parseFloat(d3.select(this).attr("height"))

      d3.select(this).attr("stroke","blue").attr("stroke-width",0.8);

      svg.append("text")
      .attr("x",xPos)
      .attr("y",yPos +height/2)
      .attr("class","graph-tooltip")
      .text(d.name +": "+ delta); 
      
    })
    .on("mouseout",function(){
      svg.select(".graph-tooltip").remove();
      d3.select(this).attr("stroke","pink").attr("stroke-width",0.2);
    })

// Legend
var legend = svg.selectAll(".legend")
    .data(['Abandoned Revenue','Recovered Revenue'].slice())
    .enter()
    .append("g")
      .attr("class", "legend")
      .attr("transform", function(d, i) { return "translate(0," + i * 20 + ")"; });

legend.append("rect")
    .attr("x", width - 48)
    .attr("width", 18)
    .attr("height", 18)
    .style("fill", color);

legend.append("text")
    .attr("x", width - 54)
    .attr("y", 9)
    .attr("dy", ".35em")
    .style("text-anchor", "end")
    .text(function(d) { return d; });