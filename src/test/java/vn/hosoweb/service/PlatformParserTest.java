package vn.hosoweb.service;

import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class PlatformParserTest {
    @Test
    void parsesAllLineFormats() {
        var r = PlatformParser.parse("""
                Facebook | Social | https://www.facebook.com/pages/create
                https://www.reddit.com/register
                Crunchbase
                Diễn đàn ABC | Forum | forum.abc.vn/register
                Medium\thttps://medium.com/m/signin
                # chú thích

                Gravatar | https://gravatar.com/signup
                """);
        assertTrue(r.errors().isEmpty(), r.errors().toString());
        var e = r.entries();
        assertEquals(6, e.size());
        assertEquals(new PlatformParser.Entry("Facebook", "Social", "https://www.facebook.com/pages/create", false, 1), e.get(0));
        assertEquals("Reddit", e.get(1).name());
        assertEquals("Forum", e.get(1).group());
        assertEquals("Crunchbase", e.get(2).name());
        assertNull(e.get(2).url());
        assertEquals("https://forum.abc.vn/register", e.get(3).url());
        assertEquals("Forum", e.get(3).group());
        assertEquals("Social", e.get(4).group());
        assertEquals("Entity", e.get(5).group());
    }

    @Test
    void captchaMarker() {
        var e = PlatformParser.parse("Reddit | Forum | https://reddit.com/register | captcha\nQuora | cần CAPTCHA").entries();
        assertTrue(e.get(0).captcha());
        assertEquals("Forum", e.get(0).group());
        assertTrue(e.get(1).captcha());
        assertEquals("Quora", e.get(1).name());
    }

    @Test
    void reportsUnknownParts() {
        var r = PlatformParser.parse("A | Social | https://a.com | lạ");
        assertEquals(1, r.entries().size());
        assertEquals(1, r.errors().size());
    }

    @Test
    void rejectsNonHttpSchemes() {
        var r = PlatformParser.parse("X | javascript:alert(1)");
        assertNull(r.entries().get(0).url());
    }
}
