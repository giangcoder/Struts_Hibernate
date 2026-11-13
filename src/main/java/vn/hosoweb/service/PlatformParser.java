package vn.hosoweb.service;

import vn.hosoweb.util.Urls;

import java.util.*;

/**
 * Phân tích danh sách nền tảng được dán vào, mỗi dòng một nền tảng:
 * <ul>
 *   <li>{@code Tên}</li>
 *   <li>{@code URL}</li>
 *   <li>{@code Tên | URL}, {@code Tên | Nhóm} hoặc {@code Tên | Nhóm | URL} (chấp nhận cả Tab thay cho |)</li>
 * </ul>
 */
public final class PlatformParser {

    public record Entry(String name, String group, String url, int lineNo) { }
    public record Result(List<Entry> entries, List<String> errors) { }

    private static final Map<String, String> KNOWN = new LinkedHashMap<>();
    static {
        for (String h : List.of("facebook.com", "instagram.com", "twitter.com", "x.com", "linkedin.com",
                "pinterest.com", "tiktok.com", "youtube.com", "tumblr.com", "threads.net", "vk.com",
                "flickr.com", "behance.net", "dribbble.com", "medium.com", "soundcloud.com", "vimeo.com",
                "telegram.org", "t.me", "zalo.me", "bsky.app", "mastodon.social", "about.me", "linktr.ee"))
            KNOWN.put(h, "Social");
        for (String h : List.of("reddit.com", "quora.com", "stackoverflow.com", "stackexchange.com",
                "voz.vn", "tinhte.vn", "webtretho.com", "otofun.net", "discourse.org"))
            KNOWN.put(h, "Forum");
        for (String h : List.of("crunchbase.com", "wikidata.org", "gravatar.com", "github.com", "gitlab.com",
                "trustpilot.com", "yelp.com", "foursquare.com", "issuu.com", "slideshare.net", "scribd.com",
                "angel.co", "wellfound.com", "producthunt.com", "g2.com", "glassdoor.com", "bbb.org"))
            KNOWN.put(h, "Entity");
    }

    private PlatformParser() { }

    public static Result parse(String text) {
        List<Entry> out = new ArrayList<>();
        List<String> errors = new ArrayList<>();
        if (text == null) return new Result(out, errors);
        String[] lines = text.split("\\r?\\n");
        for (int i = 0; i < lines.length; i++) {
            String line = lines[i].trim();
            if (line.isEmpty() || line.startsWith("#")) continue;
            int no = i + 1;
            String[] parts = line.split("\\s*[|\\t]\\s*", -1);
            String name = null, group = null, url = null;
            for (String raw : parts) {
                String p = raw.trim();
                if (p.isEmpty()) continue;
                String asUrl = Urls.normalize(p);
                if (url == null && Urls.isHttpUrl(asUrl) && !p.contains(" ")) { url = asUrl; continue; }
                String g = normalizeGroup(p);
                if (group == null && g != null && name != null) { group = g; continue; }
                if (name == null) { name = p; continue; }
                if (group == null && g != null) { group = g; continue; }
                errors.add("Dòng " + no + ": không hiểu phần \"" + p + "\"");
            }
            if (name == null && url == null) { errors.add("Dòng " + no + ": trống"); continue; }
            if (name == null) name = nameFromUrl(url);
            if (name.length() > 200) { errors.add("Dòng " + no + ": tên quá dài"); continue; }
            if (group == null) group = guessGroup(url, name);
            out.add(new Entry(name, group, url, no));
        }
        return new Result(out, errors);
    }

    /** Trả về nhóm chuẩn hóa nếu chuỗi là tên nhóm, ngược lại null. */
    public static String normalizeGroup(String s) {
        if (s == null) return null;
        String t = s.trim().toLowerCase(Locale.ROOT);
        return switch (t) {
            case "social", "mxh", "mạng xã hội", "mang xa hoi" -> "Social";
            case "entity", "thực thể", "thuc the" -> "Entity";
            case "forum", "diễn đàn", "dien dan" -> "Forum";
            case "khác", "khac", "other", "others" -> "Khác";
            default -> null;
        };
    }

    static String guessGroup(String url, String name) {
        String h = url == null ? null : Urls.host(url);
        if (h != null) {
            for (Map.Entry<String, String> e : KNOWN.entrySet()) {
                if (h.equals(e.getKey()) || h.endsWith("." + e.getKey())) return e.getValue();
            }
            if (h.startsWith("forum.") || h.startsWith("community.") || h.contains("forum")
                    || (url.toLowerCase(Locale.ROOT).contains("/forum"))) return "Forum";
        }
        String n = name == null ? "" : name.toLowerCase(Locale.ROOT);
        if (n.contains("forum") || n.contains("diễn đàn")) return "Forum";
        return "Khác";
    }

    static String nameFromUrl(String url) {
        String h = Urls.host(url);
        if (h == null) return url;
        String[] parts = h.split("\\.");
        String base = parts.length >= 2 ? parts[parts.length - 2] : parts[0];
        if (parts.length >= 3 && (base.equals("com") || base.equals("co") || base.equals("net") || base.equals("org"))) {
            base = parts[parts.length - 3];
        }
        return base.isEmpty() ? h : Character.toUpperCase(base.charAt(0)) + base.substring(1);
    }
}
