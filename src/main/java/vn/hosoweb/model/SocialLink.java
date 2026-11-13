package vn.hosoweb.model;

import jakarta.persistence.*;

/** Liên kết social của thương hiệu, dùng để gắn vào hồ sơ ở các nền tảng có hỗ trợ. */
@Entity
@Table(name = "social_link")
public class SocialLink {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    private Project project;

    @Column(length = 100) private String label;
    @Column(nullable = false, length = 1000) private String url;

    public Long getId() { return id; }
    public Project getProject() { return project; }
    public void setProject(Project p) { project = p; }
    public String getLabel() { return label; }
    public void setLabel(String v) { label = v; }
    public String getUrl() { return url; }
    public void setUrl(String v) { url = v; }
}
