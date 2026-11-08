import assert from "node:assert/strict";
import fs from "node:fs";
import path from "node:path";
import puppeteer from "puppeteer";

const root = path.resolve("public");
const proof = path.resolve("output/cycle-review");
const types = {
    ".js": "text/javascript",
    ".css": "text/css",
    ".svg": "image/svg+xml",
    ".png": "image/png",
    ".jpg": "image/jpeg",
    ".woff2": "font/woff2",
    ".woff": "font/woff",
    ".ttf": "font/ttf",
    ".json": "application/json",
};
const browser = await puppeteer.launch({
    headless: true,
    args: ["--no-sandbox"],
});
try {
    const page = await browser.newPage();
    await page.setViewport({ width: 1500, height: 1100 });
    await page.setRequestInterception(true);
    const submissions = [];
    page.on("request", async (request) => {
        if (!request.url().startsWith("http")) return request.continue();
        const url = new URL(request.url());
        const resource = [
            "marketing-campaigns",
            "auction-closings",
            "auction-reports",
        ].find((name) => url.pathname.endsWith("/" + name));
        if (request.method() === "POST") {
            submissions.push({ url: request.url(), body: request.postData() });
            return request.respond({
                status: 200,
                contentType: "text/html",
                body: "<html><body>Saved fixture</body></html>",
            });
        }
        if (
            resource &&
            (url.searchParams.has("draw") ||
                request.headers()["x-requested-with"] === "XMLHttpRequest")
        ) {
            const fixture = JSON.parse(
                fs.readFileSync(
                    path.join(proof, resource + "-index.json"),
                    "utf8",
                ),
            );
            fixture.draw = Number(url.searchParams.get("draw") || 1);
            return request.respond({
                status: 200,
                contentType: "application/json",
                body: JSON.stringify(fixture),
            });
        }
        if (url.pathname.startsWith("/__cycle-proof__/")) {
            const name = url.pathname.split("/").at(-1);
            return request.respond({
                status: 200,
                contentType: "text/html",
                body: fs.readFileSync(path.join(proof, name + ".html")),
            });
        }
        const file = path.resolve(root, "." + decodeURIComponent(url.pathname));
        if (
            file.startsWith(root + path.sep) &&
            fs.existsSync(file) &&
            fs.statSync(file).isFile()
        ) {
            return request.respond({
                status: 200,
                contentType:
                    types[path.extname(file)] || "application/octet-stream",
                headers: { "Access-Control-Allow-Origin": "*" },
                body: fs.readFileSync(file),
            });
        }
        return request.respond({ status: 204, body: "" });
    });
    const open = async (name) => {
        await page.goto("http://localhost:8000/__cycle-proof__/" + name, {
            waitUntil: "networkidle0",
        });
        await page.evaluate(() => document.fonts.ready);
    };
    await open("campaign-form");
    assert.equal(await page.$("[data-form-stepper]"), null);
    const auctionId = await page.$eval(
        '[name="auction_id"]',
        (node) =>
            Array.from(node.options).find((option) => option.value)?.value,
    );
    await page.select('[name="auction_id"]', auctionId);
    await page.select('[name="channel"]', "x");
    assert.equal(
        await page.$eval(
            '[name="metrics[watch_hours]"]',
            (node) => node.disabled,
        ),
        true,
    );
    assert.equal(await page.$('[name="platform_label"]'), null);
    assert.equal(await page.$('[name="link_role"]'), null);
    assert.equal(await page.$('[name="link_label"]'), null);
    assert.equal(await page.$('[name="metrics[clicks]"]'), null);
    assert.notEqual(await page.$('option[value="google"]'), null);
    assert.equal(await page.$('option[value="other"]'), null);
    await page.type('[name="metrics[views]"]', "0");
    assert.equal(
        await page.$eval('[name="metrics[views]"]', (node) => node.value),
        "0",
    );
    await page.screenshot({
        path: path.join(proof, "campaign-form-native.png"),
    });
    await page.select('[name="channel"]', "youtube");
    assert.equal(
        await page.$eval(
            '[name="metrics[watch_hours]"]',
            (node) => node.disabled,
        ),
        false,
    );
    assert.equal(
        await page.$eval('[name="metrics[posts]"]', (node) => node.disabled),
        true,
    );
    await page.select('[name="channel"]', "x");
    await page.select('[name="channel"]', "design");
    assert.equal(await page.$eval('[name="metrics[name_context]"]', node => node.disabled), false);
    assert.equal(await page.$eval('[name="metrics[views]"]', node => node.disabled), true);
    assert.equal(await page.$eval('[name="url"]', node => node.disabled), true);
    await page.type('[name="metrics[name_context]"]', "اسم المزاد والهوية");
    await page.screenshot({path: path.join(proof, "campaign-design-native.png")});
    await page.select('[name="channel"]', "board_before");
    const boardProperty = await page.$eval('[name="metrics[property_id]"]', node => Array.from(node.options).find(option => option.value)?.value);
    assert.ok(boardProperty);
    await page.select('[name="metrics[property_id]"]', boardProperty);
    await page.type('[name="metrics[board_count]"]', "0");
    await page.screenshot({path: path.join(proof, "campaign-boards-native.png")});
    await page.select('[name="channel"]', "x");
    await page.click("[data-save-button]");
    await page.waitForFunction(() =>
        document.body.textContent.includes("Saved fixture"),
    );
    assert.equal(submissions.length, 1);
    assert.match(submissions[0].body, /metrics\[views\]/);

    await open("closing-form");
    assert.equal(await page.$("[data-form-stepper]"), null);
    const status = await page.$eval(
        "[data-result-status]",
        (node) => node.name,
    );
    await page.select(`[name="${status}"]`, "sold");
    assert.equal(
        await page.$eval(
            '[data-result-kind="unsold"]',
            (node) => node.disabled,
        ),
        true,
    );
    await page.type('[name$="[buyer_name]"]', "اسم المشتري");
    await page.type('[name$="[sale_price]"]', "500");
    await page.screenshot({
        path: path.join(proof, "closing-form-native.png"),
    });
    await page.select(`[name="${status}"]`, "unsold");
    assert.equal(
        await page.$eval('[name$="[buyer_name]"]', (node) => node.disabled),
        true,
    );
    assert.equal(
        await page.$eval(
            '[data-result-kind="unsold"]',
            (node) => node.disabled,
        ),
        false,
    );
    await page.type('[data-result-kind="unsold"]', "سبب عدم البيع");
    await page.click("[data-save-button]");
    await page.waitForFunction(() =>
        document.body.textContent.includes("Saved fixture"),
    );
    assert.equal(submissions.length, 2);

    for (const resource of [
        "marketing-campaigns",
        "auction-closings",
        "auction-reports",
    ]) {
        await open(resource + "-index");
        await page.waitForSelector(".dt-toolbar");
        await page.waitForFunction(() =>
            document.querySelector("tbody .btn-icon"),
        );
        assert.equal((await page.$(".dt-toolbar .btn-export")) !== null, true);
        assert.equal(
            (await page.$(".dt-toolbar .btn-add")) !== null,
            resource === "marketing-campaigns",
        );
        await page.screenshot({
            path: path.join(proof, resource + "-index-native.png"),
        });
    }
    console.log(
        "Single-page component forms, shared validation/submission, conditional fields, zero values, table toolbars and icon actions passed.",
    );
} finally {
    await browser.close();
}
