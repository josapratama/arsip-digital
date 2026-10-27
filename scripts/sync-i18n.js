#!/usr/bin/env node
/**
 * 🌐 [Arsip Digital] Build-Time Translation Synchronizer
 * Downloads latest translations from Polyglot Hub (https://polyglot-hub.josapratama.com)
 * and generates clean JSON & PHP files with ZERO runtime latency.
 */

import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const PROJECT_SLUG = process.argv[2] || "arsip-digital";
const TARGET_DIR = path.resolve(process.cwd(), process.argv[3] || "resources/js/locales");
const LANG_DIR = path.resolve(process.cwd(), "lang");
const POLYGLOT_URL = (process.argv[4] || process.env.POLYGLOT_HUB_URL || "https://polyglot-hub.josapratama.com").replace(/\/+$/, "");

async function fetchWithRetry(url, retries = 3) {
  for (let i = 0; i < retries; i++) {
    try {
      const res = await fetch(url);
      if (res.ok) return res;
    } catch (e) {
      if (i === retries - 1) throw e;
      await new Promise(r => setTimeout(r, 600));
    }
  }
}

async function syncTranslations() {
  console.log("\n======================================================");
  console.log("🌐 [Polyglot Hub] Build-Time Translation Sync");
  console.log("======================================================");
  console.log(`📦 Project     : "${PROJECT_SLUG}"`);
  console.log(`📡 Server URL  : ${POLYGLOT_URL}`);
  console.log(`📁 Target Dir  : ${TARGET_DIR}`);

  if (!fs.existsSync(TARGET_DIR)) {
    fs.mkdirSync(TARGET_DIR, { recursive: true });
  }
  if (!fs.existsSync(LANG_DIR)) {
    fs.mkdirSync(LANG_DIR, { recursive: true });
  }

  const languages = [
    { code: "id", name: "Bahasa Indonesia" },
    { code: "en", name: "English" },
    { code: "ja", name: "Japanese" }
  ];

  let successCount = 0;
  const flatDicts = {};

  for (const lang of languages) {
    // 1. Fetch nested format for Vue $t("nav.dashboard")
    const nestedUrl = `${POLYGLOT_URL}/api/v1/export/${PROJECT_SLUG}/${lang.code}?common=true&format=nested`;
    // 2. Fetch flat format for Laravel lang/*.json and messages.php
    const flatUrl = `${POLYGLOT_URL}/api/v1/export/${PROJECT_SLUG}/${lang.code}?common=true&format=flat`;

    try {
      const resNested = await fetchWithRetry(nestedUrl);
      const resFlat = await fetchWithRetry(flatUrl);

      const nestedDict = await resNested.json();
      const flatDict = await resFlat.json();
      flatDicts[lang.code] = flatDict;

      // Write frontend nested locale
      const filePath = path.join(TARGET_DIR, `${lang.code}.json`);
      fs.writeFileSync(filePath, JSON.stringify(nestedDict, null, 2), "utf-8");

      // Write Laravel root lang/*.json
      const langJsonPath = path.join(LANG_DIR, `${lang.code}.json`);
      fs.writeFileSync(langJsonPath, JSON.stringify(flatDict, null, 2), "utf-8");

      // Generate Laravel PHP messages.php in lang/[code]/messages.php
      const langSubDir = path.join(LANG_DIR, lang.code);
      if (!fs.existsSync(langSubDir)) {
        fs.mkdirSync(langSubDir, { recursive: true });
      }

      const messagesOnly = {};
      for (const [k, v] of Object.entries(flatDict)) {
        if (k.startsWith("messages.")) {
          messagesOnly[k.replace("messages.", "")] = v;
        }
      }

      let phpContent = "<?php\n\nreturn [\n";
      for (const [k, v] of Object.entries(messagesOnly)) {
        const escaped = String(v).replace(/'/g, "\\'");
        phpContent += `    '${k}' => '${escaped}',\n`;
      }
      phpContent += "];\n";
      fs.writeFileSync(path.join(langSubDir, "messages.php"), phpContent, "utf-8");

      const keyCount = Object.keys(flatDict).length;
      console.log(`  ✓ Synced ${lang.code}.json (${keyCount} keys) + messages.php`);
      successCount++;
    } catch (err) {
      console.error(`  ❌ Error syncing ${lang.code}:`, err.message);
    }
  }

  // Generate phrases.json lookup dictionary for fast runtime mapping
  if (flatDicts.id && flatDicts.en && flatDicts.ja) {
    const phrases = { id: {}, en: {}, ja: {} };
    const allKeys = Object.keys(flatDicts.id);
    for (const key of allKeys) {
      const idVal = flatDicts.id[key];
      const enVal = flatDicts.en[key];
      const jaVal = flatDicts.ja[key];
      if (!idVal) continue;

      if (idVal) {
        phrases.id[idVal] = idVal;
        if (enVal) phrases.en[idVal] = enVal;
        if (jaVal) phrases.ja[idVal] = jaVal;
      }
      if (enVal) {
        phrases.id[enVal] = idVal;
        phrases.en[enVal] = enVal;
        if (jaVal) phrases.ja[enVal] = jaVal;
      }
    }
    const phrasePath = path.join(TARGET_DIR, "phrases.json");
    fs.writeFileSync(phrasePath, JSON.stringify(phrases, null, 2), "utf-8");
    console.log(`  ✓ Generated phrases.json (${Object.keys(phrases.ja).length} phrase mappings)`);
  }

  console.log(`\n✨ Successfully synced ${successCount} languages! Zero runtime lag.`);
  console.log("======================================================\n");
}

syncTranslations().catch((err) => {
  console.error("Sync failed:", err);
  process.exit(1);
});
