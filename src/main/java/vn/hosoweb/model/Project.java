package vn.hosoweb.model;

import jakarta.persistence.*;
import java.time.LocalDateTime;

/** Một dự án = một website / thương hiệu cần tạo hồ sơ trên nhiều nền tảng. */
@Entity
@Table(name = "project")
public class Project {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    private User owner;

    // --- Thông tin thương hiệu ---
    @Column(nullable = false, length = 200) private String brandName;
    @Column(length = 500) private String website;
    @Column(length = 1000) private String logoUrl;
    @Column(length = 1000) private String coverUrl;
    @Column(length = 300) private String title;
    @Column(length = 1000) private String shortDescription;
    @Lob @Column(columnDefinition = "CLOB") private String fullDescription;
    @Column(length = 1000) private String seoKeywords;
    @Column(length = 1000) private String hashtags;
    @Column(length = 100) private String username1;
    @Column(length = 100) private String username2;
    @Column(length = 100) private String username3;
    @Column(length = 100) private String market = "Việt Nam";
    @Column(length = 100) private String language = "Tiếng Việt";

    // --- Thông tin theo dự án ---
    @Column(length = 200) private String gmail;
    @Column(length = 50) private String gmailStatus = "Chưa kết nối";
    @Column(length = 200) private String manager;
    @Column(length = 20) private String birthDate;
    @Column(length = 50) private String smsPhone;
    @Column(length = 200) private String otpHandler;
    @Column(length = 200) private String industry;
    @Column(length = 100) private String companySize;
    @Column(length = 100) private String businessType;
    @Column(length = 200) private String publicEmail;
    @Column(length = 50) private String publicPhone;
    @Column(length = 500) private String address;
    @Column(length = 300) private String openingHours;
    @Column(length = 1000) private String googleMapsUrl;

    private LocalDateTime createdAt = LocalDateTime.now();
    private LocalDateTime updatedAt = LocalDateTime.now();

    /** Sao chép các trường người dùng được phép sửa (không đụng tới id / owner). */
    public void copyEditableFrom(Project f) {
        brandName = trim(f.brandName); website = trim(f.website); logoUrl = trim(f.logoUrl);
        coverUrl = trim(f.coverUrl); title = trim(f.title); shortDescription = trim(f.shortDescription);
        fullDescription = trim(f.fullDescription); seoKeywords = trim(f.seoKeywords); hashtags = trim(f.hashtags);
        username1 = trim(f.username1); username2 = trim(f.username2); username3 = trim(f.username3);
        market = orDefault(trim(f.market), "Việt Nam"); language = orDefault(trim(f.language), "Tiếng Việt");
        gmail = trim(f.gmail); gmailStatus = orDefault(trim(f.gmailStatus), "Chưa kết nối");
        manager = trim(f.manager); birthDate = trim(f.birthDate); smsPhone = trim(f.smsPhone);
        otpHandler = trim(f.otpHandler); industry = trim(f.industry); companySize = trim(f.companySize);
        businessType = trim(f.businessType); publicEmail = trim(f.publicEmail); publicPhone = trim(f.publicPhone);
        address = trim(f.address); openingHours = trim(f.openingHours); googleMapsUrl = trim(f.googleMapsUrl);
        updatedAt = LocalDateTime.now();
    }

    private static String trim(String s) { return s == null ? null : (s.trim().isEmpty() ? null : s.trim()); }
    private static String orDefault(String s, String d) { return s == null ? d : s; }

    public java.util.List<String> getUsernames() {
        java.util.List<String> l = new java.util.ArrayList<>();
        for (String u : new String[]{username1, username2, username3}) if (u != null && !u.isBlank()) l.add(u);
        return l;
    }

    public Long getId() { return id; }
    public User getOwner() { return owner; }
    public void setOwner(User owner) { this.owner = owner; }
    public String getBrandName() { return brandName; }
    public void setBrandName(String v) { brandName = v; }
    public String getWebsite() { return website; }
    public void setWebsite(String v) { website = v; }
    public String getLogoUrl() { return logoUrl; }
    public void setLogoUrl(String v) { logoUrl = v; }
    public String getCoverUrl() { return coverUrl; }
    public void setCoverUrl(String v) { coverUrl = v; }
    public String getTitle() { return title; }
    public void setTitle(String v) { title = v; }
    public String getShortDescription() { return shortDescription; }
    public void setShortDescription(String v) { shortDescription = v; }
    public String getFullDescription() { return fullDescription; }
    public void setFullDescription(String v) { fullDescription = v; }
    public String getSeoKeywords() { return seoKeywords; }
    public void setSeoKeywords(String v) { seoKeywords = v; }
    public String getHashtags() { return hashtags; }
    public void setHashtags(String v) { hashtags = v; }
    public String getUsername1() { return username1; }
    public void setUsername1(String v) { username1 = v; }
    public String getUsername2() { return username2; }
    public void setUsername2(String v) { username2 = v; }
    public String getUsername3() { return username3; }
    public void setUsername3(String v) { username3 = v; }
    public String getMarket() { return market; }
    public void setMarket(String v) { market = v; }
    public String getLanguage() { return language; }
    public void setLanguage(String v) { language = v; }
    public String getGmail() { return gmail; }
    public void setGmail(String v) { gmail = v; }
    public String getGmailStatus() { return gmailStatus; }
    public void setGmailStatus(String v) { gmailStatus = v; }
    public String getManager() { return manager; }
    public void setManager(String v) { manager = v; }
    public String getBirthDate() { return birthDate; }
    public void setBirthDate(String v) { birthDate = v; }
    public String getSmsPhone() { return smsPhone; }
    public void setSmsPhone(String v) { smsPhone = v; }
    public String getOtpHandler() { return otpHandler; }
    public void setOtpHandler(String v) { otpHandler = v; }
    public String getIndustry() { return industry; }
    public void setIndustry(String v) { industry = v; }
    public String getCompanySize() { return companySize; }
    public void setCompanySize(String v) { companySize = v; }
    public String getBusinessType() { return businessType; }
    public void setBusinessType(String v) { businessType = v; }
    public String getPublicEmail() { return publicEmail; }
    public void setPublicEmail(String v) { publicEmail = v; }
    public String getPublicPhone() { return publicPhone; }
    public void setPublicPhone(String v) { publicPhone = v; }
    public String getAddress() { return address; }
    public void setAddress(String v) { address = v; }
    public String getOpeningHours() { return openingHours; }
    public void setOpeningHours(String v) { openingHours = v; }
    public String getGoogleMapsUrl() { return googleMapsUrl; }
    public void setGoogleMapsUrl(String v) { googleMapsUrl = v; }
    public LocalDateTime getCreatedAt() { return createdAt; }
    public LocalDateTime getUpdatedAt() { return updatedAt; }
    public void touch() { updatedAt = LocalDateTime.now(); }
}
