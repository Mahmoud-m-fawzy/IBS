<?php
// Reports Tab Content
?>
<!-- Reports Tab -->
<div id="reports" class="tab-content">
    <div class="section">
        <h2 data-translate="reports.title">📊 Reports</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <h3 id="today-sales">EGP 0</h3>
                <p data-translate="reports.todaySales">Today's Sales</p>
            </div>
            <div class="stat-card">
                <h3 id="today-profit">EGP 0</h3>
                <p data-translate="reports.todayProfit">Today's Profit</p>
            </div>
            <div class="stat-card">
                <h3 id="month-sales">EGP 0</h3>
                <p data-translate="reports.monthSales">This Month Sales</p>
            </div>
            <div class="stat-card">
                <h3 id="month-profit">EGP 0</h3>
                <p data-translate="reports.monthProfit">This Month Profit</p>
            </div>
            <div class="stat-card">
                <h3 id="total-products">0</h3>
                <p data-translate="reports.totalProducts">Total Products</p>
            </div>
            <div class="stat-card">
                <h3 id="low-stock">0</h3>
                <p data-translate="reports.lowStock">Low Stock Items</p>
            </div>
        </div>

        <!-- Print Reports Section -->
        <div style="margin-top: 30px;">
            <h3
                style="color: #0056b3; margin-bottom: 20px; font-size: 1.3em; border-left: 4px solid #0056b3; padding-left: 15px;"
                data-translate="reports.printReports">🖨️ Print Reports</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">

                <!-- Today's Sales Report -->
                <div style="background: linear-gradient(135deg, #0056b3 0%, #007bff 100%); color: white; padding: 25px; border-radius: 15px; box-shadow: 0 8px 25px rgba(0, 86, 179, 0.3); transition: transform 0.3s ease;"
                    onmouseover="this.style.transform='translateY(-5px)'"
                    onmouseout="this.style.transform='translateY(0)'">
                    <div style="display: flex; align-items: center; margin-bottom: 15px;">
                        <div
                            style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 50%; margin-right: 15px;">
                            <span style="font-size: 24px;">📅</span>
                        </div>
                        <div>
                            <h4 style="margin: 0; font-size: 1.2em;">Today's Sales</h4>
                            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">Detailed sales report
                                for today</p>
                        </div>
                    </div>
                    <button onclick="printTodaysSalesReport()"
                        style="width: 100%; background: rgba(255,255,255,0.2); color: white; border: 2px solid rgba(255,255,255,0.3); padding: 12px; border-radius: 8px; font-size: 16px; cursor: pointer; transition: all 0.3s ease;"
                        onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                        onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                        🖨️ Print Today's Sales
                    </button>
                </div>

                <!-- This Month's Sales Report -->
                <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 25px; border-radius: 15px; box-shadow: 0 8px 25px rgba(240, 147, 251, 0.3); transition: transform 0.3s ease;"
                    onmouseover="this.style.transform='translateY(-5px)'"
                    onmouseout="this.style.transform='translateY(0)'">
                    <div style="display: flex; align-items: center; margin-bottom: 15px;">
                        <div
                            style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 50%; margin-right: 15px;">
                            <span style="font-size: 24px;">📊</span>
                        </div>
                        <div>
                            <h4 style="margin: 0; font-size: 1.2em;">This Month's Sales</h4>
                            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">Complete monthly sales
                                report</p>
                        </div>
                    </div>
                    <button onclick="printMonthSalesReport()"
                        style="width: 100%; background: rgba(255,255,255,0.2); color: white; border: 2px solid rgba(255,255,255,0.3); padding: 12px; border-radius: 8px; font-size: 16px; cursor: pointer; transition: all 0.3s ease;"
                        onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                        onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                        🖨️ Print Monthly Report
                    </button>
                </div>

                <!-- Total Products Report -->
                <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 25px; border-radius: 15px; box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3); transition: transform 0.3s ease;"
                    onmouseover="this.style.transform='translateY(-5px)'"
                    onmouseout="this.style.transform='translateY(0)'">
                    <div style="display: flex; align-items: center; margin-bottom: 15px;">
                        <div
                            style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 50%; margin-right: 15px;">
                            <span style="font-size: 24px;">📦</span>
                        </div>
                        <div>
                            <h4 style="margin: 0; font-size: 1.2em;">Total Products</h4>
                            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">Complete inventory list
                            </p>
                        </div>
                    </div>
                    <button onclick="printProductsReport()"
                        style="width: 100%; background: rgba(255,255,255,0.2); color: white; border: 2px solid rgba(255,255,255,0.3); padding: 12px; border-radius: 8px; font-size: 16px; cursor: pointer; transition: all 0.3s ease;"
                        onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                        onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                        🖨️ Print Products List
                    </button>
                </div>

                <!-- Low Stock Items Report -->
                <div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 25px; border-radius: 15px; box-shadow: 0 8px 25px rgba(250, 112, 154, 0.3); transition: transform 0.3s ease;"
                    onmouseover="this.style.transform='translateY(-5px)'"
                    onmouseout="this.style.transform='translateY(0)'">
                    <div style="display: flex; align-items: center; margin-bottom: 15px;">
                        <div
                            style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 50%; margin-right: 15px;">
                            <span style="font-size: 24px;">⚠️</span>
                        </div>
                        <div>
                            <h4 style="margin: 0; font-size: 1.2em;">Low Stock Items</h4>
                            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">Items requiring
                                restocking</p>
                        </div>
                    </div>
                    <button onclick="printLowStockReport()"
                        style="width: 100%; background: rgba(255,255,255,0.2); color: white; border: 2px solid rgba(255,255,255,0.3); padding: 12px; border-radius: 8px; font-size: 16px; cursor: pointer; transition: all 0.3s ease;"
                        onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                        onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                        🖨️ Print Low Stock Alert
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>
