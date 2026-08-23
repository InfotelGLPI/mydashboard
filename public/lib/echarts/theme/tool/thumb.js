/**
 * -------------------------------------------------------------------------
 * mydashboard plugin for GLPI
 * Copyright (C) 2016-2026 by the mydashboard Development Team.
 *
 * https://github.com/InfotelGLPI/mydashboard
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of mydashboard.
 *
 * mydashboard is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * mydashboard is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

/*
* Licensed to the Apache Software Foundation (ASF) under one
* or more contributor license agreements.  See the NOTICE file
* distributed with this work for additional information
* regarding copyright ownership.  The ASF licenses this file
* to you under the Apache License, Version 2.0 (the
* "License"); you may not use this file except in compliance
* with the License.  You may obtain a copy of the License at
*
*   http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing,
* software distributed under the License is distributed on an
* "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
* KIND, either express or implied.  See the License for the
* specific language governing permissions and limitations
* under the License.
*/

const glob = require('glob');
const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

async function wait(time) {
    return new Promise(resolve => {
        setTimeout(resolve, time);
    });
}

async function snapshot(browser, themePath) {
    let themeName = path.basename(themePath, '.js');
    let code = fs.readFileSync(themePath, 'utf-8');

    let page = await browser.newPage();
    await page.evaluateOnNewDocument(code);
    await page.setViewport({ width: 1200, height: 1200 });
    try {
        await page.goto('http://localhost/echarts/theme/tool/thumb.html#' + themeName);
        await wait(200);
        await page.screenshot({ path: __dirname + '/../thumb/' + themeName + '.png' });
    }
    catch (e) {
        console.log(e);
    }
    await page.close();

    console.log('Updated ' + themeName);
}

glob('../*.js', async function (err, themePathList) {

    let browser = await puppeteer.launch();
    for (let themePath of themePathList) {
        try {
            await snapshot(browser, themePath);
        }
        catch(e) {
            console.log(e);
        }
    }
    await browser.close();
});